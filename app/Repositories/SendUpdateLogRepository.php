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
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Traits\PersonalQuoteSyncTrait;
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
        try {
            $category = $data['childCategory']['slug']; // EF, EN, CI, CIR, CPU, CPD.
            $count = $this->fetchGetCount($category); // get count of send update log by category.
            $baseCode = $category.'-'.date('m').date('y').'-'; // CPD-0824- or EF-0824- etc.
            $code = $baseCode.($count + 1); // CPD-0824-48 or EF-0824-48 etc.
            $quoteServiceFile = $insuranceProviderId = $plan_id = null;

            $attempts = 0;
            while (SendUpdateLog::where('code', $code)->exists() && $attempts < 10) {
                $count++;
                $code = $baseCode.$count;
                $attempts++;
            }

            if ($attempts >= 10) {
                vAbort('Send Update Log Code generation failed.');
            }

            $uuid = strtoupper(Str::random(6));

            // Todo:: Check if personal quote exists because its break when quote not in personal quotes
            $personalQuote = PersonalQuoteRepository::where([
                'quote_type_id' => $data['quote_type_id'],
                'uuid' => $data['quote_uuid'],
            ])->first();

            if (! $personalQuote) {
                return (object) [
                    'message' => 'Quote sync pending, please try again later. ',
                ];
            }

            $data['personal_quote_id'] = $personalQuote?->id ?? null;
            $option = ! empty($data['option_id']) ? LookupRepository::find($data['option_id'])->code : null;

            $quoteType = QuoteTypes::getName($data['quote_type_id'])->value;
            if (checkPersonalQuotes($quoteType)) {
                $realQuote = $personalQuote;
            } else {
                $quoteServiceFile = app(getServiceObject($quoteType));
                $realQuote = $quoteServiceFile->getEntity($data['quote_uuid']);
            }

            // it will check if send update type is Correction of Policy Details or Endorsement Financial with subtype Policy Period Extension, it will save
            // insurance_provider_id and plan_id.
            if ($category == SendUpdateLogStatusEnum::CPD || ($category == SendUpdateLogStatusEnum::EF && $option == SendUpdateLogStatusEnum::PPE)) {
                if (in_array($data['quote_type_id'], [QuoteTypeId::Car, QuoteTypeId::Travel, QuoteTypeId::Health])) {
                    $quoteType = QuoteTypes::getName($data['quote_type_id'])->value;
                    $quoteModel = app($quoteServiceFile)->getEntityPlain($realQuote->id)->load(['payments', 'plan']);
                    $payment = $quoteModel->payments()->mainLeadPayment()->first();

                    $planRelationName = strtolower($quoteType).'Plan';
                    $payment->load($planRelationName);
                    $insuranceProvider = $payment->$planRelationName?->insuranceProvider;
                    $insuranceProviderId = $insuranceProvider->id ?? null;
                    $plan_id = $quoteModel->plan?->id ?? null;
                } else {
                    $insuranceProviderId = $realQuote->insurance_provider_id ?? null;
                }
            }

            // if the send update category is 'Cancellation from Inception', 'Cancellation from Inception and reissuance' or 'Endorsement Financial' with
            // subtype 'Midterm policy cancellation, then it will update the quote status to 'Cancellation Pending'.
            if (in_array($category, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR]) || ($category == SendUpdateLogStatusEnum::EF && $option == SendUpdateLogStatusEnum::MPC)) {
                if (! checkPersonalQuotes($quoteType)) {
                    $model = 'App\\Models\\'.$quoteType.'Quote';
                    $personalQuote = $model::where('uuid', $data['quote_uuid'])->first();
                }
                $personalQuote->quote_status_id = QuoteStatusEnum::CancellationPending;
                QuoteStatusLog::create([
                    'quote_type_id' => $data['quote_type_id'],
                    'quote_request_id' => $data['personal_quote_id'],
                    'current_quote_status_id' => QuoteStatusEnum::CancellationPending,
                ]);

                $personalQuote->save();
            }

            $sendUpdate = $this->create([
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
            ]);
            if ($category == SendUpdateLogStatusEnum::CPD || ($category == SendUpdateLogStatusEnum::EF && $option == SendUpdateLogStatusEnum::PPE)) {
                $this->checkPolicyDetailsFilled($sendUpdate, $data['quote_type_id'], $realQuote);
            }
        } catch (\Exception $ex) {
            $sendUpdate = (object) [
                'message' => $ex->getMessage(),
            ];
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
        try {
            $sendUpdate = $this->find($id)->update([
                'notes' => $data['notes'],
                'option_id' => $data['option_id'],
                'car_addons' => $data['car_addons'] ?? null,
                'emirates_id' => $data['emirates_id'] ?? null,
                'seating_capacity' => $data['seating_capacity'] ?? null,
            ]);
        } catch (\Exception $ex) {
            $sendUpdate = (object) [
                'message' => $ex->getMessage(),
            ];
        }

        return $sendUpdate;
    }

    public function fetchGetCount($code)
    {
        // in code where clause, added - hyphen sign to get actual difference like CI and CIR.
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
        try {
            $sendUpdate = $this->find($data['id']);

            $result = $sendUpdate->update([
                'price_with_vat' => $data['price_with_vat'],
                'price_vat_applicable' => $data['price_vat_applicable'],
                'price_vat_not_applicable' => $data['price_vat_not_applicable'],
                'insurer_quote_number' => $data['insurer_quote_number'],
                'insurance_provider_id' => $data['insurance_provider_id'],
                'status' => ! in_array($sendUpdate->status, [SendUpdateLogStatusEnum::TRANSACTION_APPROVED, SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER]) ? SendUpdateLogStatusEnum::REQUEST_IN_PROGRESS : $sendUpdate->status,
            ]);
            $this->updatePayment($data);
        } catch (\Exception $ex) {
            info('SendUpdate id: '.$data['id'].' '.$ex->getMessage());
            $result = (object) [
                'message' => $ex->getMessage(),
            ];
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
            }

            return $payments->save();
        }

        return null;
    }

    public function fetchSavePolicyDetails($data)
    {
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
            info('Unable to save Policy Details - SendUpdateUUID: '.$sendUpdate->uuid.' - Error: '.$ex->getMessage());
        }

        return $result;
    }

    public function fetchSaveProviderDetails($data)
    {
        try {
            $result = $this->find($data['send_update_log_id'])->update([
                'insurance_provider_id' => $data['insurance_provider_id'],
            ]);
        } catch (\Exception $ex) {
            $result = (object) [
                'message' => $ex->getMessage(),
            ];
        }

        return $result;
    }

    public function fetchSendUpdateToCustomer($data)
    {
        $sendUpdateLog = $this->find($data['sendUpdateId']);
        try {
            if ($data['quoteType'] == quoteTypeCode::Car && $sendUpdateLog->category->code == SendUpdateLogStatusEnum::EN) {
                $quote = CarQuote::where('uuid', $sendUpdateLog->quote_uuid)->first();
                if (! empty($sendUpdateLog->emirates_id)) { // will work on Change of Emirates (with no financial impact).
                    $quote->update(['emirate_of_registration_id' => $sendUpdateLog->emirates_id]);
                } elseif (! empty($sendUpdateLog->seating_capacity) && $sendUpdateLog->seating_capacity != 0) { // will work on Change in seating capacity (with no financial impact).
                    $quote->update(['seat_capacity' => $sendUpdateLog->seating_capacity]);
                }
            }

            dispatch(new SendUpdateToCustomerJob($sendUpdateLog, $data));

            /*$sendUpdateLog->update([
                'status' => SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER,
            ]);*/

            // temporary comments.
            /*if (! $sendUpdateLog->is_email_sent) {
                dispatch(new SendUpdateToCustomerJob($sendUpdateLog, $data));
            } else {
                $sendUpdateLog->update([
                    'status' => SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER,
                ]);
            }*/
            info('Send update to Customer - Send Update Code: '.$sendUpdateLog->code.' - Status update to: '.SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER);
            $result = true;
        } catch (\Exception $ex) {
            logger()->error('Send Update to Customer - Failed - Send Update Code: '.$sendUpdateLog->code.' - Error : '.$ex->getMessage());

            $result = (object) [
                'message' => $ex->getMessage(),
            ];
        }

        return $result;
    }

    public function fetchSaveBookingDetails($data)
    {
        $sendUpdate = $this->find($data['id']);
        try {
            $sendUpdateLogService = app(SendUpdateLogService::class);
            $isNegative = $sendUpdateLogService->isNegativeValue($sendUpdate);
            $bookingDetails = [
                'is_booking_filled' => SendUpdateLogStatusEnum::BOOKING_FILLED,
                // 'booking_date' => $data['booking_date'], // commented this because it will update when Sage Invoice created through Send Update
                'invoice_description' => $data['invoice_description'],
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
                $bookingDetails = array_merge($bookingDetails, ['reversal_invoice' => $data['reversal_invoice']]);
            }

            $payment = Payment::where('send_update_log_id', $data['id'])->first();
            $insuranceProviderId = $sendUpdate->insurance_provider_id ?? null;
            if ($payment) {
                $sendUpdateLogService = app(SendUpdateLogService::class);
                info('Send update - Updating Booking details and Commission Schedule in Payments - SendUpdateUUID: '.$sendUpdate->uuid);
                $sendUpdateLogService->sendUpdatePriceAndDiscount($sendUpdate, $payment);
                $sendUpdateLogService->updatePaymentDetails($payment, $sendUpdate, true);
                app(SplitPaymentService::class)->updateCommissionSchedule($payment);

                $insuranceProviderId = $insuranceProviderId ?? $payment->insurance_provider_id ?? null;
            }

            if ($insuranceProviderId && ($insuranceProvider = InsuranceProviderRepository::where('id', $insuranceProviderId)->first())) {
                $bookingDetails['broker_invoice_number'] = $sendUpdateLogService->generateBrokerInvoiceNumber($sendUpdate, $insuranceProvider);
            } else {
                vAbort('Send Update Log provider code not found.');
            }

            $result = $sendUpdate->update($bookingDetails);

        } catch (\Exception $ex) {
            $result = (object) [
                'message' => $ex->getMessage(),
            ];
            info('Unable to save Booking Details - SendUpdateUUID: '.$sendUpdate->uuid.' - Error: '.$ex->getMessage());
        }

        return $result;
    }

    public function fetchEndorsementsByPersonalQuoteId($personalQuoteId)
    {
        return $this->where('personal_quote_id', $personalQuoteId)->where(function ($q) {
            $q->where('code', 'like', '%EF%')->orWhere('code', 'like', '%EN%');
        })->orderBy('id', 'desc')->get();
    }

    public function checkPolicyDetailsFilled($sendUpdate, $quoteTypeId, $quote)
    {
        $insuranceProviderId = ($sendUpdate->insurance_provider_id ?? $quote->insurance_provider_id) ?? null;
        if ($quoteTypeId == QuoteTypeId::Car && is_null($insuranceProviderId)) {
            $insuranceProviderId = $quote->car_plan_provider_id ?? null;
        }

        $sendUpdatePolicyDetails = [
            'first_name' => ($sendUpdate->first_name ?? $quote->first_name) ?? null,
            'last_name' => ($sendUpdate->last_name ?? $quote->last_name) ?? null,
            'insurance_provider_id' => $insuranceProviderId,
            'policy_number' => ($sendUpdate->policy_number ?? $quote->policy_number) ?? null,
            'issuance_date' => ($sendUpdate->issuance_date ?? $quote->policy_issuance_date) ?? null,
            'start_date' => ($sendUpdate->start_date ?? $quote->policy_start_date) ?? null,
            'expiry_date' => ($sendUpdate->expiry_date ?? $quote->policy_expiry_date) ?? null,
        ];

        if (in_array($quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Travel, QuoteTypeId::Health])) {
            $sendUpdatePolicyDetails['plan_id'] = ($sendUpdate->plan_id ?? $quote->plan_id) ?? null;
        }

        $filledValues = array_filter($sendUpdatePolicyDetails, function ($value) {
            return ! is_null($value) && $value !== '';
        });

        if (count($sendUpdatePolicyDetails) === count($filledValues)) {
            $sendUpdate->is_policy_filled = SendUpdateLogStatusEnum::POLICY_FILLED;
            $sendUpdate->save();
        }
    }

    public function fetchSendUpdateOptions($quoteTypeId, $parentId, $status, $businessInsuranceTypeId = null)
    {
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
                return ! in_array($item->slug, [SendUpdateLogStatusEnum::ACB, SendUpdateLogStatusEnum::ATIB]);
            });
        }

        return $response;
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
        return $this->where('insurer_tax_invoice_number', $data['taxInvoiceNo'])
            ->where('quote_uuid', $data['quoteUuid'])
            ->first() ?? null;
    }

    public function fetchGetSendUpdateLogInvoices($quoteTypeId, $quoteUuid)
    {
        return $this->query()
            ->where('quote_uuid', $quoteUuid)
            ->where('quote_type_id', $quoteTypeId)
            ->where('status', SendUpdateLogStatusEnum::UPDATE_BOOKED)
            ->whereNotNull('insurer_tax_invoice_number')
            ->get()
            ->pluck('insurer_tax_invoice_number');
    }
}
