<?php

namespace App\Repositories;

use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Jobs\SendUpdateToCustomerJob;
use App\Models\CarQuote;
use App\Models\Lookup;
use App\Models\Payment;
use App\Models\QuoteStatusLog;
use App\Models\SendUpdateLog;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\Logger\LoggerService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Traits\PersonalQuoteSyncTrait;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SendUpdateLogRepository extends BaseRepository
{
    use PersonalQuoteSyncTrait;

    public function model()
    {
        return SendUpdateLog::class;
    }

    public function fetchCreate($data)
    {
        LoggerService::info('fn:fetchCreate - Start - SendUpdateLogRepository');
        try {
            $category = $data['childCategory']['slug']; // EF, EN, CI, CIR, CPU, CPD.
            $count = $this->fetchGetCount($category); // get count of send update log by category.
            $baseCode = $category.'-'.date('m').date('y').'-'; // CPD-0824- or EF-0824- etc.
            $code = $baseCode.($count + 1); // CPD-0824-48 or EF-0824-48 etc.
            LoggerService::info('Code generated for new send update log', extra: ['code' => $code]);
            $quoteServiceFile = $insuranceProviderId = $plan_id = null;

            $attempts = 0;
            while (SendUpdateLog::where('code', $code)->exists() && $attempts < 10) {
                $count++;
                $code = $baseCode.$count;
                $attempts++;
            }

            if ($attempts >= 10) {
                LoggerService::info('Send Update Log Code generation failed after 10 attempts');
                vAbort('Send Update Log Code generation failed.');
            }

            $uuid = strtoupper(Str::random(6));
            LoggerService::info('UUID generated for new send update log', extra: ['code' => $code, 'uuid' => $uuid]);

            $personalQuote = $this->updatePersonalQuote($data['quote_uuid'], $data['quote_type_id'], []);

            $data['personal_quote_id'] = $personalQuote?->id ?? null;
            $option = ! empty($data['option_id']) ? LookupRepository::find($data['option_id'])->code : null;

            $quoteType = QuoteTypes::getName($data['quote_type_id'])->value;
            if (checkPersonalQuotes($quoteType)) {
                $quote = $personalQuote;
            } else {
                $quoteServiceFile = app(getServiceObject($quoteType));
                $quote = $quoteServiceFile->getEntity($data['quote_uuid']);
            }

            // it will check if send update type is Correction of Policy Details or Endorsement Financial with subtype Policy Period Extension, it will save
            // insurance_provider_id.
            $policyDetails = [];
            if (
                $category == SendUpdateLogStatusEnum::CPD
                || ($category == SendUpdateLogStatusEnum::EF && $option == SendUpdateLogStatusEnum::PPE)
            ) {
                @[$insuranceProviderId, $plan_id] = app(SendUpdateLogService::class)->getProviderDetails($quote, $data['quote_type_id'], true);
                $policyDetails = $this->autoFillPolicyDetails($quote, $data['quote_type_id'], $insuranceProviderId, $category, $plan_id);
            } elseif ($quote->insly_id || $quote->insly_migrated) {
                $insuranceProviderId = $quote?->insurance_provider_id ?? null;
                LoggerService::info('Insurance Provider ID: '.$insuranceProviderId.' selected for Send Update (Legacy) - uuid: '.$uuid.' quote_uuid: '.$data['quote_uuid']);
            }

            // if the send update category is 'Cancellation from Inception', 'Cancellation from Inception and reissuance' or 'Endorsement Financial' with
            // subtype 'Midterm policy cancellation, then it will update the quote status to 'Cancellation Pending'.
            if (in_array($category, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR]) || ($category == SendUpdateLogStatusEnum::EF && $option == SendUpdateLogStatusEnum::MPC)) {
                if (! checkPersonalQuotes($quoteType)) {
                    $model = 'App\\Models\\'.$quoteType.'Quote';
                    $personalQuote = $model::where('uuid', $data['quote_uuid'])->first();
                }
                $this->fetchUpdateQuoteStatusLog($data['quote_type_id'], $data['quote_uuid'], QuoteStatusEnum::CancellationPending);
                $personalQuote->quote_status_id = QuoteStatusEnum::CancellationPending;
                QuoteStatusLog::create([
                    'quote_type_id' => $data['quote_type_id'],
                    'quote_request_id' => $data['personal_quote_id'],
                    'current_quote_status_id' => QuoteStatusEnum::CancellationPending,
                ]);

                LoggerService::info('Quote status changed', extra: [
                    'code' => $code,
                    'uuid' => $uuid,
                    'current_quote_status_id' => QuoteStatusEnum::CancellationPending,
                ]);

                $personalQuote->save();
            }

            $sendUpdate = $this->create(array_merge([
                'personal_quote_id' => $data['personal_quote_id'],
                'quote_uuid' => $data['quote_uuid'],
                'quote_type_id' => $data['quote_type_id'],
                'category_id' => $data['childCategory']['id'],
                'option_id' => $data['option_id'],
                'status' => $data['status'],
                'uuid' => $uuid,
                'code' => $code,
                'insurance_provider_id' => $insuranceProviderId,
                'plan_id' => $plan_id,
                'created_by' => auth()->user()->id,
            ], $policyDetails));

            LoggerService::info('Send Update Log created successfully - uuid: '.$sendUpdate->uuid.' quote_uuid: '.$sendUpdate->quote_uuid);
        } catch (\Exception $ex) {
            $sendUpdate = (object) [
                'message' => $ex->getMessage(),
            ];

            LoggerService::error('Unable to create SendUpdateLog', exception: $ex);
        }

        return $sendUpdate;
    }

    public function fetchGetLogByUuid($uuid)
    {
        return $this->where('uuid', $uuid)->firstOrFail();
    }

    public function fetchGetLogById($id)
    {
        return $this->where('id', $id)->firstOrFail();
    }

    public function fetchUpdateLog($id, $data)
    {
        LoggerService::info('fn:fetchUpdateLog - Start - SendUpdateLogRepository');

        try {
            $sendUpdate = $this->find($id)->update([
                'notes' => $data['notes'],
                'option_id' => $data['option_id'],
                'car_addons' => $data['car_addons'] ?? null,
                'emirates_id' => $data['emirates_id'] ?? null,
                'seating_capacity' => $data['seating_capacity'] ?? null,
                'endorsement_number' => $data['endorsement_number'] ?? null,
            ]);
        } catch (\Exception $ex) {
            $sendUpdate = (object) [
                'message' => $ex->getMessage(),
            ];

            LoggerService::error('Unable to update SendUpdateLog', exception: $ex);
        }

        return $sendUpdate;
    }

    /**
     * Fetches and returns the count of records with a specific code pattern for the current month
     * in code where clause, added - hyphen sign to get actual difference like CI and CIR.
     *
     * @param  string  $code  The code prefix to search for (will be matched with a trailing hyphen)
     * @return int The count of matching records for the current month
     */
    public function fetchGetCount($code)
    {
        LoggerService::info('fn:fetchGetCount - Start - SendUpdateLogRepository');

        return $this->where('code', 'like', "%$code-%")->whereMonth('created_at', '=', date('m'))->count();
    }

    public function fetchFindByQuoteUuid($uuid)
    {
        return $this->with(['category', 'option'])
            ->where('quote_uuid', $uuid)
            ->get();
    }

    public function fetchUpdateLogPriceDetails($data)
    {
        LoggerService::info('fn:fetchUpdateLogPriceDetails - Start - SendUpdateLogRepository');
        try {
            $sendUpdate = $this->find($data['id']);
            if (! in_array($sendUpdate->status, [SendUpdateLogStatusEnum::TRANSACTION_APPROVED, SendUpdateLogStatusEnum::UPDATE_ISSUED, SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER])) {
                $status = SendUpdateLogStatusEnum::REQUEST_IN_PROGRESS;
                info('Send Update uuid -> '.$sendUpdate->uuid.' - Status changing to -> '.$status);
                app(CentralService::class)->updateSendUpdateStatusLogs($sendUpdate->id, $sendUpdate->status, SendUpdateLogStatusEnum::REQUEST_IN_PROGRESS);
            }

            $result = $sendUpdate->update([
                'price_with_vat' => $data['price_with_vat'],
                'price_vat_applicable' => $data['price_vat_applicable'],
                'price_vat_not_applicable' => $data['price_vat_not_applicable'],
                'insurer_quote_number' => $data['insurer_quote_number'],
                'insurance_provider_id' => $data['insurance_provider_id'],
                'status' => $status ?? $sendUpdate->status,
            ]);
            if ($sendUpdate->payments->isNotEmpty() && $sendUpdate->payments[0]) {
                app(CentralService::class)->synchronizePaymentInformation($sendUpdate, $sendUpdate->payments[0]);
            }
        } catch (\Exception $ex) {
            $result = (object) [
                'message' => $ex->getMessage(),
            ];

            LoggerService::error('Unable to update SendUpdateLog Price details', exception: $ex);
        }

        return $result;
    }

    public function updatePayment($data)
    {
        $result = $this->where('id', $data['id'])->with('payments')->first();

        if ($result->payments->isNotEmpty()) {
            $payments = $result->payments[0];
            $payments->total_price = $data['price_with_vat'];

            if ($payments->payment_status_id == PaymentStatusEnum::PAID) {
                $payments->payment_status_id = PaymentStatusEnum::PARTIALLY_PAID;
                if ($data['price_with_vat'] < ($payments->total_amount + $payments->discount_value)) {
                    app(SendUpdateLogService::class)->updatePaymentTotalPrice($payments, $data['price_with_vat']);
                }
            }

            return $payments->save();
        }

        return null;
    }

    public function fetchSavePolicyDetails($data)
    {
        LoggerService::info('fn:fetchSavePolicyDetails - Start - SendUpdateLogRepository');

        $sendUpdate = $this->find($data['id']);
        try {
            $result = $sendUpdate->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'insurance_provider_id' => $data['insurance_provider_id'],
                'plan_id' => $data['plan_id'],
                'policy_number' => $data['policy_number'],
                'issuance_date' => $data['issuance_date'],
                'start_date' => $data['start_date'],
                'expiry_date' => $data['expiry_date'],
                'insurer_quote_number' => $data['insurer_quote_number'] ?? null,
                'issuance_status_id' => $data['issuance_status_id'] ?? null,
                'is_policy_filled' => SendUpdateLogStatusEnum::POLICY_FILLED,
            ]);
        } catch (\Exception $ex) {
            $result = (object) [
                'message' => $ex->getMessage(),
            ];
            LoggerService::error('Unable to save Policy Details', exception: $ex);
        }

        return $result;
    }

    public function fetchSaveProviderDetails($data)
    {
        LoggerService::info('fn:fetchSaveProviderDetails - Start - SendUpdateLogRepository');

        try {
            $result = $this->find($data['send_update_log_id'])->update([
                'insurance_provider_id' => $data['insurance_provider_id'],
            ]);
        } catch (\Exception $ex) {
            $result = (object) [
                'message' => $ex->getMessage(),
            ];

            LoggerService::error('Unable to update ProviderDetails', exception: $ex);
        }

        return $result;
    }

    public function fetchSendUpdateToCustomer($request)
    {
        LoggerService::info('fn:fetchSendUpdateToCustomer - SendUpdateLogRepository');
        $sendUpdateLog = $this->find($request['sendUpdateId']);
        if (empty($sendUpdateLog->notes)) {
            $response[] = ['status' => 500, 'message' => 'Notes are required to send update to customer'];

            return $response;
        }

        try {
            if (isset($request['action']) && $request['action'] == SendUpdateLogStatusEnum::ACTION_SNBU) {
                LoggerService::info('Send and Update Book Button found');
                $endorsementResponse = app(SendUpdateLogService::class)->preparedDataForEndorsement((object) $request);
                $response[] = ['status' => $endorsementResponse['status'] ? 200 : 500, 'message' => $endorsementResponse['message']];

                if ($endorsementResponse['status'] && ! empty($endorsementResponse['sageRequestPayload'])) {
                    $request['dispatchSageCall'] = true;
                    $request['sageRequestPayload'] = $endorsementResponse['sageRequestPayload'];
                    $request['ccPaymentProcess'] = $endorsementResponse['ccPaymentProcess'];
                }
            }

            if ($request['quoteType'] == quoteTypeCode::Car && $sendUpdateLog->category->code == SendUpdateLogStatusEnum::EN) {
                $quote = CarQuote::where('uuid', $sendUpdateLog->quote_uuid)->first();
                if (! empty($sendUpdateLog->emirates_id)) {
                    LoggerService::info('fn:SendUpdateToCustomer - Updating Emirates ID - SendUpdateCode: '.$sendUpdateLog->code.' - Emirates ID: '.$sendUpdateLog->emirates_id);
                    $quote->update(['emirate_of_registration_id' => $sendUpdateLog->emirates_id]);
                } elseif (! empty($sendUpdateLog->seating_capacity) && $sendUpdateLog->seating_capacity != 0) {
                    LoggerService::info('fn:SendUpdateToCustomer - Updating Seating Capacity - SendUpdateCode: '.$sendUpdateLog->code.' - Seating Capacity: '.$sendUpdateLog->seating_capacity);
                    $quote->update(['seat_capacity' => $sendUpdateLog->seating_capacity]);
                }
            }

            if ($sendUpdateLog->is_email_sent) {
                $response[] = ['status' => 200, 'message' => 'Email already sent to customer'];
            } else {
                $response[] = ['status' => 200, 'message' => 'Send Update to customer email is being scheduled'];
            }

            SendUpdateToCustomerJob::dispatch($sendUpdateLog, $request)->onQueue('insly');
            LoggerService::info('fn:SendUpdateToCustomer - Process End - SendUpdateCode: '.$sendUpdateLog->code.' - Status updating to '.SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER);
        } catch (\Exception $ex) {
            $response = ['status' => 500, 'message' => 'Something went wrong, please try again later'];

            LoggerService::error('Unable to SendUpdateToCustomer', exception: $ex);
        }

        return $response;
    }

    public function fetchSaveBookingDetails($data)
    {
        LoggerService::info('fn:fetchSaveBookingDetails - Start - SendUpdateLogRepository');

        $sendUpdate = $this->find($data['id']);
        try {
            $sendUpdateLogService = app(SendUpdateLogService::class);
            $isNegative = $sendUpdateLogService->isNegativeValue($sendUpdate);
            $bookingDetails = [
                'is_booking_filled' => SendUpdateLogStatusEnum::BOOKING_FILLED,
                // 'booking_date' => $data['booking_date'], // commented this because it will update when Sage Invoice created through Send Update
                // 'invoice_description' => $data['invoice_description'],
                'transaction_payment_status' => $data['transaction_payment_status'],
                'invoice_date' => $data['invoice_date'],
                'insurer_tax_invoice_number' => $data['insurer_tax_invoice_number'] ?? null,
                'insurer_commission_invoice_number' => $data['insurer_commission_invoice_number'] ?? null,
                'discount' => $data['discount'],
                'commission_percentage' => strToFloat($data['commission_percentage'] ?? null),
                'commission_vat_not_applicable' => $data['commission_vat_not_applicable'] ?? null,
                'vat_on_commission' => $data['vat_on_commission'] ?? null,
                'total_commission' => $data['total_commission'] ?? null,
                'total_vat_amount' => $data['total_vat_amount'] ?? null,
                'price_vat_applicable' => strToFloat($data['price_vat_applicable'] ?? null, $isNegative),
                'price_vat_not_applicable' => strToFloat($data['price_vat_not_applicable'] ?? null, $isNegative),
                'commission_vat_applicable' => strToFloat($data['commission_vat_applicable'] ?? null, $isNegative),
                'price_with_vat' => $data['price_with_vat'] ?? null,
            ];
            // it will check if send update type is CPD then it will add reversal_invoice to $data because other send update types don't have 2 kind of
            // booking details, so we don't need to add null reversal_invoice on other options details.
            if ($sendUpdate->category->code == SendUpdateLogStatusEnum::CPD) {
                $bookingDetails['reversal_invoice'] = $data['reversal_invoice'];
            }

            $result = $sendUpdate->update($bookingDetails);
            LoggerService::info('Send Update Log Updated successfully');
            $sendUpdate->save(); // This save is used because sometime object not refresh properly
            $sendUpdate->refresh();

            $payment = Payment::where('send_update_log_id', $data['id'])->first();
            if ($payment) {
                $sendUpdateLogService = app(SendUpdateLogService::class);
                LoggerService::info('Send update - Updating Booking details and Commission Schedule in Payments - SendUpdateUUID: '.$sendUpdate->uuid);
                app(CentralService::class)->synchronizePaymentInformation($sendUpdate, $payment);
                $sendUpdateLogService->updatePaymentDetails($payment, $sendUpdate, true);
                app(SplitPaymentService::class)->updateCommissionSchedule($payment);
                if ($payment->discount_value && (empty($sendUpdate->discount) || $sendUpdate->discount == 0)) {
                    $sendUpdate->update(['discount' => $payment->discount_value]);
                }
            }

        } catch (\Exception $ex) {
            $result = (object) [
                'message' => $ex->getMessage(),
            ];
            LoggerService::error('Unable to save Booking Details', exception: $ex);
        }

        return $result;
    }

    public function autoFillPolicyDetails($quote, $quoteTypeId, $insuranceProviderId, $category, $planId = null): array
    {
        if ($quoteTypeId == QuoteTypeId::Travel) { // policy_expiry_date format is different in TravelQuoteService file.
            $quote->policy_expiry_date = Carbon::createFromFormat('d-m-Y', $quote->policy_expiry_date)->format('Y-m-d');
        }

        $policyDetails = [
            'first_name' => $quote->first_name ?? null,
            'last_name' => $quote->last_name ?? null,
            'policy_number' => $quote->policy_number ?? null,
            'issuance_date' => $quote->policy_issuance_date ?? null,
            'start_date' => $quote->policy_start_date ?? null,
            'expiry_date' => $quote->policy_expiry_date ?? null,
        ];

        $isPolicyFilled = app(SendUpdateLogService::class)->isPolicyDetailsFilled($policyDetails, $quoteTypeId, $insuranceProviderId, $planId, $category, $quote);

        if ($isPolicyFilled) {
            $policyDetails = array_merge($policyDetails, [
                'is_policy_filled' => true,
            ]);
        }

        return $policyDetails;
    }

    public function fetchSendUpdateOptions($quoteTypeId, $parentId, $status, $businessInsuranceTypeId = null)
    {
        LoggerService::info('fn:fetchSendUpdateOptions - Start - SendUpdateLogRepository');

        $query = Lookup::where('quote_type_id', $quoteTypeId)->where('parent_id', $parentId);
        if ($quoteTypeId == QuoteTypeId::Business && in_array($status, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::EN])) {
            if (! in_array($businessInsuranceTypeId, [quoteBusinessTypeCode::getId(quoteBusinessTypeCode::carFleet), quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical)])) {
                $businessInsuranceTypeId = null;
            }
        } else {
            $businessInsuranceTypeId = null;
        }

        $response = $query->sendUpdateOptions($quoteTypeId, $parentId, $businessInsuranceTypeId)->get();

        $checkAdditionalBookingPermission = auth()->user()->hasPermissionTo(PermissionsEnum::SEND_UPDATE_ADD_BOOKING);
        if (! $checkAdditionalBookingPermission) {
            $response = $response->filter(function ($item) {
                return ! in_array($item->slug, [SendUpdateLogStatusEnum::ACB, SendUpdateLogStatusEnum::ATIB, SendUpdateLogStatusEnum::ATICB, SendUpdateLogStatusEnum::ATCRNB, SendUpdateLogStatusEnum::ATCRNB_RBB]);
            });
        }

        return $response->values();
    }

    /*
     * we don't need to push this on production, need to remove this before production.
     */
    public function fetchIsCategoryOrOptionAvailable($categoryId, $optionId): bool
    {

        if (! Lookup::find($categoryId)) {
            return false;
        }

        if (! empty($optionId)) {
            if (! Lookup::find($optionId)) {
                return false;
            }
        }

        return true;
    }

    public function fetchGetLogByTaxInvoiceNumber($data)
    {
        LoggerService::info('fn:fetchGetLogByTaxInvoiceNumber - Start - SendUpdateLogRepository');

        return $this->where('insurer_tax_invoice_number', $data['taxInvoiceNo'])
            ->where('quote_uuid', $data['quoteUuid'])
            ->first() ?? null;
    }

    public function fetchGetSendUpdateLogInvoices($quoteTypeId, $quoteUuid)
    {
        LoggerService::info('fn:fetchGetSendUpdateLogInvoices - Start - SendUpdateLogRepository');

        return $this->query()
            ->where('quote_uuid', $quoteUuid)
            ->where('quote_type_id', $quoteTypeId)
            ->where('status', SendUpdateLogStatusEnum::UPDATE_BOOKED)
            ->whereNotNull('insurer_tax_invoice_number')
            ->get()
            ->pluck('insurer_tax_invoice_number');
    }

    public function fetchUpdateInsurerDetails($sendUpdate, $insurerDetails)
    {
        try {
            $sendUpdatePayload = [
                'invoice_description' => $insurerDetails['invoice_description'],
                'insurance_provider_id' => $insurerDetails['insurance_provider_id'],
                'plan_id' => $insurerDetails['plan_id'],
            ];

            $sendUpdate->update($sendUpdatePayload);

            if (! $sendUpdate->payments->isEmpty()) {
                app(SendUpdateLogService::class)->updatePaymentDetails($sendUpdate->payments->first(), $sendUpdate, false, $insurerDetails);
            }

            info('Send Update insurer details successfully updated - uuid: '.$sendUpdate->uuid);

        } catch (\Exception $ex) {
            logger()->error('Error while updating Send Update insurer details - uuid: '.$sendUpdate->uuid.' - Exception: '.$ex->getMessage());

            return false;
        }

        return true;
    }

    public function fetchCancelSendUpdate($sendUpdateLogId, $cancelReason)
    {
        $sendUpdate = SendUpdateLog::where('id', $sendUpdateLogId)->firstOrFail();
        LoggerService::info('fn:fetchCancelSendUpdate - SendUpdateLogRepository');

        try {
            $oldStatus = $sendUpdate->status;

            $sendUpdate->update([
                'cancel_reason' => $cancelReason,
                'status' => SendUpdateLogStatusEnum::REQUEST_CANCELLED,
            ]);
            app(CentralService::class)->updateSendUpdateStatusLogs($sendUpdate->id, $oldStatus, SendUpdateLogStatusEnum::REQUEST_CANCELLED);

            LoggerService::info('SendUpdateLog cancelled successfully');

            if (in_array($sendUpdate?->category?->code, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR]) || ($sendUpdate?->option?->code == SendUpdateLogStatusEnum::MPC)) {
                $quoteType = QuoteTypes::getName($sendUpdate->quote_type_id)->value;

                $modelClass = 'App\\Models\\'.$quoteType.'Quote';
                $quote = $modelClass::where('uuid', $sendUpdate->quote_uuid)->firstOrFail();
                $previousStatusId = $quote->quote_status_id;

                $leadHistoryLogs = app(CRUDService::class)->getLeadHistoryLogs($sendUpdate->quote_type_id, $quote->id);
                $beforeEndorsementStatus = $leadHistoryLogs->skip(1)->first();

                if (! $beforeEndorsementStatus) {
                    $beforeEndorsementStatus = $leadHistoryLogs->first();
                }

                $beforeEndorsementStatusId = $beforeEndorsementStatus->currentQuoteStatus->id;

                $quote->update(['quote_status_id' => $beforeEndorsementStatusId]);

                QuoteStatusLog::create([
                    'quote_type_id' => $sendUpdate->quote_type_id,
                    'quote_request_id' => $quote->id,
                    'current_quote_status_id' => $beforeEndorsementStatusId,
                    'previous_quote_status_id' => $previousStatusId,
                    'created_by' => auth()->user()->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                LoggerService::info('Quote status updated successfully', extra: [
                    'quoteUUID' => $quote->uuid,
                    'previousStatusId' => $previousStatusId,
                    'newStatusId' => $beforeEndorsementStatusId,
                ]);
            }

            $response = ['message' => 'Send Update Log cancelled successfully.', 'status' => 200];
        } catch (\Exception $ex) {
            LoggerService::error('Error while cancelling SendUpdateLog', exception: $ex);

            $response = ['message' => 'Error while cancelling Send Update Log.', 'status' => 500];
        }
        LoggerService::info('fn:fetchCancelSendUpdate ended');

        return $response;
    }

    public function fetchUpdateQuoteStatusLog($quoteTypeId, $quoteUuid, $quoteStatusId)
    {
        info('function fetchUpdateQuoteStatusLog started - quote UUID: '.$quoteUuid);

        try {
            $quoteType = QuoteTypes::getName($quoteTypeId)->value;
            $modelClass = 'App\\Models\\'.$quoteType.'Quote';
            $quote = $modelClass::where('uuid', $quoteUuid)->firstOrFail();
            $previousStatusId = $quote->quote_status_id;

            QuoteStatusLog::create([
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quote->id,
                'current_quote_status_id' => $quoteStatusId,
                'previous_quote_status_id' => $previousStatusId,
                'created_by' => auth()->user()->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            info('Quote status updated successfully - quote UUID: '.$quoteUuid.', Status changed from '.$previousStatusId.' to '.$quoteStatusId);
        } catch (\Exception $ex) {
            info('Error while updating Quote status - quote UUID: '.$quoteUuid.' - Exception: '.$ex->getMessage());
        }
        info('function fetchUpdateQuoteStatusLog ended - quote UUID: '.$quoteUuid);
    }

    public function fetchGetQuoteFromSendUpdateLog($sendUpdateLog)
    {
        $quoteType = QuoteTypes::getName($sendUpdateLog->quote_type_id)->value;

        $modelClass = 'App\\Models\\'.$quoteType.'Quote';

        return $modelClass::where('uuid', $sendUpdateLog->quote_uuid)->first();
    }
}
