<?php

namespace App\Repositories;

use App\Enums\LookupsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\CarQuote;
use App\Models\Lookup;
use App\Models\Payment;
use App\Models\QuoteStatusLog;
use App\Models\SendUpdateLog;
use App\Services\SendUpdateLogService;
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
            $code = $data['childCategory']['slug'];
            $count = $this->fetchGetCount($code);

            $code = $code.'-'.date('m').date('y').'-'.($count + 1);

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

            if (! empty($personalQuote->insurance_provider_id)) {
                $insuranceProvider = InsuranceProviderRepository::getById($personalQuote->insurance_provider_id);
            }

            $res = $this->create([
                'personal_quote_id' => $data['personal_quote_id'],
                'quote_uuid' => $data['quote_uuid'],
                'quote_type_id' => $data['quote_type_id'],
                'category_id' => $data['childCategory']['id'],
                'option_id' => $data['option_id'],
                'status' => $data['status'],
                'uuid' => $uuid,
                'code' => $code,
                'provider_name' => isset($insuranceProvider) ? $insuranceProvider->text : '',
                'insurance_provider_id' => $personalQuote->insurance_provider_id ?? null,
            ]);
            // it will check if send update type is Correction of Policy Details or Enorsement Financial with subtype Policy Period Extension, it will save
            // insurance_provider_id and plan_id.
            $quoteType = QuoteTypes::getName($data['quote_type_id'])->value;
            if ($res->category->code == SendUpdateLogStatusEnum::CPD || ($res->category->code == SendUpdateLogStatusEnum::EF && $res->option->code == SendUpdateLogStatusEnum::PPE)) {

                if (checkPersonalQuotes($quoteType)) {
                    $realQuote = $personalQuote;
                } else {
                    $quoteServiceFile = app(getServiceObject($quoteType));
                    $realQuote = $quoteServiceFile->getEntity($res->quote_uuid);
                }
                if (in_array($data['quote_type_id'], [QuoteTypeId::Car, QuoteTypeId::Travel, QuoteTypeId::Health])) {
                    $serviceFile = 'App\\Services\\'.$quoteType.'QuoteService';

                    $quoteModel = app($serviceFile)->getEntityPlain($realQuote->id)->load(['plan']);
                    $res->insurance_provider_id = $quoteModel->plan->provider_id ?? null;
                    $res->plan_id = $quoteModel->plan->id ?? null;
                    $res->plan_name = $quoteModel->plan->text ?? null;
                } else {
                    $res->insurance_provider_id = $realQuote->insuranceProvider->id ?? $realQuote->insurance_provider_id ?? null;
                    $res->provider_name = $realQuote->insuranceProvider->text ?? $realQuote->insurance_provider_text ?? null;
                }

                $res->save();
                $res->refresh();
                $this->checkPolicyDetailsFilled($res, $data['quote_type_id'], $realQuote);
            }

            // if the send update category is 'Cancellation from Inception', 'Cancellation from Inception and reissuance' or 'Endorsement Financial' with
            // subtype 'Midterm policy cancellation, then it will update the quote status to 'Cancellation Pending'.
            if (in_array($res->category->code, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR]) ||
                ($res->category->code == SendUpdateLogStatusEnum::EF && $res->option->code == SendUpdateLogStatusEnum::MPC)) {
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
        } catch (\Throwable $th) {
            $res = (object) [
                'message' => $th->getMessage(),
            ];
        }

        return $res;
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
            $log = $this->find($id)->update([
                'notes' => $data['notes'],
                'option_id' => $data['option_id'],
                'car_addons' => $data['car_addons'] ?? null,
                'emirates_id' => $data['emirates_id'] ?? null,
                'seating_capacity' => $data['seating_capacity'] ?? null,
            ]);
        } catch (\Exception $ex) {
            $log = (object) [
                'message' => $ex->getMessage(),
            ];
        }

        return $log;
    }

    public function fetchGetCount($code)
    {
        // in code where clause, added - hyphen sign to get actual difference like CI and CIR.
        return $this->where('code', 'like', "%$code-%")->whereMonth('created_at', '=', date('m'))->count();
    }

    public function fetchFindByQuoteUuid($uuid)
    {
        return $this->where(['quote_uuid' => $uuid])->get();
    }

    public function fetchUpdateLogPriceDetails($data)
    {
        try {
            $result = $this->find($data['id']);

            $result->update([
                'price_with_vat' => $data['price_with_vat'],
                'price_vat_applicable' => $data['price_vat_applicable'],
                'price_vat_not_applicable' => $data['price_vat_not_applicable'],
                'insurer_quote_number' => $data['insurer_quote_number'],
                'insurance_provider_id' => $data['insurance_provider_id'],
                'status' => ! in_array($result->status, [SendUpdateLogStatusEnum::TRANSACTION_APPROVED, SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER]) ?
                    SendUpdateLogStatusEnum::REQUEST_IN_PROGRESS : $result->status,
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
        try {
            $result = $this->where('id', $data['id'])->update([
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
        }

        return $result;
    }

    public function fetchSendUpdateToCustomer($data)
    {
        try {
            $sendUpdateLog = $this->find($data['sendUpdateId']);
            if ($data['quoteType'] == quoteTypeCode::Car && $sendUpdateLog->category->code == SendUpdateLogStatusEnum::EN) {
                $quote = CarQuote::where('uuid', $sendUpdateLog->quote_uuid)->first();
                if (! empty($sendUpdateLog->emirates_id)) { // will work on Change of Emirates (with no financial impact).
                    $quote->update(['emirate_of_registration_id' => $sendUpdateLog->emirates_id]);
                } elseif (! empty($sendUpdateLog->seating_capacity) && $sendUpdateLog->seating_capacity != 0) { // will work on Change in seating capacity (with no financial impact).
                    $quote->update(['seat_capacity' => $sendUpdateLog->seating_capacity]);
                }
            }

            $result = $sendUpdateLog->update([
                'status' => SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER,
            ]);
            info('Send update to Customer - Send Update Code: '.$sendUpdateLog->code.' - Status update to: '.SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER);
        } catch (\Exception $ex) {
            logger()->error('Send Update to Customer - Failed - Send Update Code: '.$sendUpdateLog->code.' - Error : '.$ex->getMessage());
            $result = (object) [
                'message' => $ex->getMessage(),
            ];
        }

        return $result;
    }

    public function fetchSaveBookingDetails($request)
    {
        try {
            $res = $this->find($request['id']);
            $isNegative = app(SendUpdateLogService::class)->isNegativeValue($res);

            $data = [
                'is_booking_filled' => SendUpdateLogStatusEnum::BOOKING_FILLED,
                // 'booking_date' => $request['booking_date'], // commented this because it will update when Sage Invoice created through Send Update
                'invoice_description' => $request['invoice_description'],
                'broker_invoice_number' => $request['broker_invoice_number'],
                'transaction_payment_status' => $request['transaction_payment_status'],
                'invoice_date' => $request['invoice_date'],
                'insurer_tax_invoice_number' => $request['insurer_tax_invoice_number'],
                'insurer_commission_invoice_number' => $request['insurer_commission_invoice_number'],
                'discount' => $request['discount'],
                'commission_percentage' => strToFloat($request['commission_percentage']),
                'commission_vat_not_applicable' => $request['commission_vat_not_applicable'],
                'vat_on_commission' => $request['vat_on_commission'],
                'total_commission' => $request['total_commission'],
                'total_vat_amount' => $request['total_vat_amount'],
                'price_vat_applicable' => strToFloat($request['price_vat_applicable'], $isNegative),
                'price_vat_not_applicable' => strToFloat($request['price_vat_not_applicable'], $isNegative),
                'commission_vat_applicable' => strToFloat($request['commission_vat_applicable'], $isNegative),
                'price_with_vat' => $request['price_with_vat'],
            ];
            // it will check if send update type is CPD then it will add reversal_invoice to $data because other send update types don't have 2 kind of
            // booking details, so we don't need to add null reversal_invoice on other options details.
            if ($request['send_update_type'] == SendUpdateLogStatusEnum::CPD) {
                $data = array_merge($data, ['reversal_invoice' => $request['reversal_invoice']]);
            }
            $res = $res->update($data);

            $payment = Payment::where('send_update_log_id', $request['id'])->firstOrFail();
            if ($payment) {
                $bookingDetailsTotalPrice = floatval($request['price_with_vat']);
                if ($bookingDetailsTotalPrice > $payment->total_amount) {
                    $diff = number_format($bookingDetailsTotalPrice - $payment->total_amount, 2);
                    if ($diff < 1) {
                        $payment->discount_value = $diff;
                        $payment->discount_type = LookupsEnum::SYSTEM_ADJUSTED_DISCOUNT;
                    } else {
                        $payment->total_price = $bookingDetailsTotalPrice;
                        $payment->payment_status_id = PaymentStatusEnum::PARTIALLY_PAID;
                    }
                } elseif ($bookingDetailsTotalPrice == $payment->total_amount && $payment->discount_value && $payment->discount_type == LookupsEnum::SYSTEM_ADJUSTED_DISCOUNT->value) {
                    $payment->discount_value = 0;
                    $payment->discount_type = null;
                }

                $payment->save();
            }
        } catch (\Exception $ex) {
            $res = (object) [
                'message' => $ex->getMessage(),
            ];
            info($ex->getMessage());
        }

        return $res;
    }

    public function fetchEndorsementsByPersonalQuoteId($personalQuoteId)
    {
        return $this->where('personal_quote_id', $personalQuoteId)->where(function ($q) {
            $q->where('code', 'like', '%EF%')->orWhere('code', 'like', '%EN%');
        })->orderBy('id', 'desc')->get();
    }

    public function checkPolicyDetailsFilled($sendUpdate, $quoteTypeId, $quote)
    {
        $sendUpdatePolicyDetails = [
            'first_name' => ($sendUpdate->first_name ?? $quote->first_name) ?? null,
            'last_name' => ($sendUpdate->last_name ?? $quote->last_name) ?? null,
            'insurance_provider_id' => ($sendUpdate->insurance_provider_id ?? ($quote->insurance_provider_id ?? $quote->car_plan_provider_id)) ?? null,
            'policy_number' => ($sendUpdate->policy_number ?? $quote->policy_number) ?? null,
            'issuance_date' => ($sendUpdate->issuance_date ?? $quote->policy_issuance_date) ?? null,
            'start_date' => ($sendUpdate->start_date ?? $quote->policy_start_date) ?? null,
            'expiry_date' => ($sendUpdate->expiry_date ?? $quote->renewal_expiry_date) ?? null,
            'insurer_quote_number' => ($sendUpdate->insurer_quote_number ?? $quote->insurer_quote_number) ?? null,
            'issuance_status_id' => ($sendUpdate->issuance_status_id ?? $quote->policy_issuance_status_id) ?? null,
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

        return $query->sendUpdateOptions($quoteTypeId, $parentId, $businessInsuranceTypeId)->get();
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
            ->get()
            ->pluck('insurer_tax_invoice_number');
    }
}
