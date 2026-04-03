<?php

namespace App\Services;

use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Http\Controllers\V2\CentralController;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\PaymentStatusHistory;
use App\Models\PaymentStatusLog;
use App\Models\QuoteStatusLog;
use App\Repositories\SendUpdateLogRepository;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentService extends BaseService
{
    /**
     * This method calculates the difference between the price with VAT and the total payment amount, which includes the captured amount and any discount value.
     * If the difference is less than $1 but more than $0, it adjusts the payment's discount value to account for this difference
     * This method trigger when policy details section update
     *
     * @return float
     */
    public function processMasterPayment($payment, $quoteObject, $isCreditCardEnabled = true)
    {
        $priceWithVat = round((float) ($quoteObject->price_with_vat ?? 0), 2);
        $this->setPaymentStatusBasedOnPrice($priceWithVat, $payment);

        $payment->total_price = $priceWithVat;
        $priceVatApplicable = (float) ($quoteObject->price_vat_applicable ?? 0);
        $priceVatNotApplicable = (float) ($quoteObject->price_vat_not_applicable ?? 0);
        $payment->price_vat_applicable = $priceVatApplicable + $priceVatNotApplicable;
        $payment->price_vat = (float) ($quoteObject->vat ?? $quoteObject->total_vat_amount ?? 0);

        $this->setTotalAmount(payment: $payment);

        if (! $isCreditCardEnabled && $payment->payment_methods_code == PaymentMethodsEnum::CreditCard && $payment->isInsurerPayment() && ! in_array($payment->payment_status_id, [PaymentStatusEnum::PAID, PaymentStatusEnum::AUTHORISED])) {
            $payment->payment_methods_code = PaymentMethodsEnum::InsurerPayment;
        }

        if ($payment->isDirty()) {
            Payment::withoutEvents(function () use ($payment) {
                $payment->save();
            });
        }
    }

    /**
     * This method will set payment status in payment table
     * This method trigger when policy details section update
     */
    public function setPaymentStatusBasedOnPrice($priceWithVat, $payment): void
    {
        if ($payment->payment_methods_code != PaymentMethodsEnum::CreditApproval) {
            $captureAndDiscount = round((float) ($payment->captured_amount ?? 0) + (float) ($payment->discount_value ?? 0), 2);
            // If status is partially paid & total price is less than price with vat then set status to partially paid
            if ($captureAndDiscount < $priceWithVat && in_array($payment->payment_status_id, [PaymentStatusEnum::PAID, PaymentStatusEnum::AUTHORISED])) {
                $payment->payment_status_id = PaymentStatusEnum::PARTIALLY_PAID;
            } elseif ($priceWithVat <= $captureAndDiscount) {
                $payment->payment_status_id = PaymentStatusEnum::PAID;
            }
        }
    }

    /**
     * Set the total payment price when payment frequency is upfront
     */
    private function setTotalAmount($payment)
    {
        LoggerService::info('Quote Code: '.$payment->code.' Updating TA frequency is : '.$payment->frequency.' and payment_status_id: '.$payment->payment_status_id);
        if ($payment && $payment->frequency == PaymentFrequency::UPFRONT && in_array($payment->payment_status_id, [PaymentStatusEnum::PAID, PaymentStatusEnum::NEW, PaymentStatusEnum::OVERDUE])) {
            $totalPrice = (float) ($payment->total_price ?? 0);
            $discountValue = (float) ($payment->discount_value ?? 0);
            $totalAmount = $totalPrice - $discountValue;
            info('Quote Code: '.$payment->code.' updateTotalAmount - totalPrice: '.$totalPrice.', discountValue: '.$discountValue.', totalAmount: '.$totalAmount);
            $payment->total_amount = $totalAmount;
        }
    }

    /**
     * Retry posting prepayment to Sage for a given payment split and quote.
     */
    public function retryCreatePrepayment(array $data): array
    {
        $srNo = $data['sr_no'];
        $paymentCode = $data['payment_code'];
        LoggerService::info("retryCreatePrepayment called for payment code : {$paymentCode} and sr no : {$srNo}");

        try {
            $paymentSplit = PaymentSplits::find($data['payment_split_id']);
            $payment = $paymentSplit->payment;
            $sendUpdateId = $payment->send_update_log_id;
            $mainLeadObject = app(CentralController::class)->getQuoteObject($data['quote_type'], $data['quote_request_id']);
            if (! empty($sendUpdateId) && $sendUpdateId > 0) {
                $quoteModel = SendUpdateLogRepository::getLogById($sendUpdateId);
                $quoteModel->fill([
                    'customer_id' => $mainLeadObject->customer_id,
                    'advisor_id' => $mainLeadObject->advisor_id,
                ]);
            } else {
                $quoteModel = $mainLeadObject;
            }
            $request = new Request;
            $request->merge([
                'modelType' => $data['quote_type'],
                'quoteType' => $data['quote_type'],
                'quote_id' => $data['quote_request_id'],
                'customer_id' => $quoteModel->customer_id,
                'advisor_id' => $quoteModel->advisor_id,
            ]);

            LoggerService::info("Start Retry Prepayment Posting of Payment split for payment code : {$paymentCode} and sr no : {$srNo}");
            if ((new SageApiService)->isSageEnabled()) {
                $sageARPrepaymentResponse = (new SageApiService)->createARPrepaymentPremiumReceipt($request, $quoteModel, $payment, $paymentSplit, $paymentSplit->collection_amount);

                if (! $sageARPrepaymentResponse['status']) {
                    LoggerService::warning("Sage response error for payment code : {$paymentCode} and sr no : {$srNo}");
                    vAbort($sageARPrepaymentResponse['message']);
                }
                LoggerService::info("Sage AR Receipt ID created: {$paymentSplit->sage_reciept_id} for payment code : {$paymentCode} and sr no : {$srNo}");

                /*$request->sage_customer_number = $sageARPrepaymentResponse['sageCustomerNumber'];

                $sageAPPrepaymentResponse = (new SageApiService)->createAPPrepaymentPremiumReceipt($request, $quoteModel, $payment, $paymentSplit, $paymentSplit->collection_amount);

                if (! $sageAPPrepaymentResponse['status']) {
                    vAbort($sageAPPrepaymentResponse['message']);

                }*/

                LoggerService::info("Sage AP Receipt ID created: {$paymentSplit->sage_ap_payment_receipt_id} for payment code : {$paymentCode} and sr no : {$srNo}");

                return [
                    'success' => true,
                    'message' => 'Prepayment posting to Sage successfully.',
                    'sage_receipt_id' => $paymentSplit->sage_reciept_id,
                ];
            }

            return [
                'success' => false,
                'message' => 'Sage integration is not enabled.',
            ];
        } catch (\Exception $exception) {
            LoggerService::warning("Exception in retryPrepaymentPostingToSage: {$exception->getMessage()} for payment code : {$paymentCode} and sr no : {$srNo}");

            return [
                'success' => false,
                'message' => 'Prepayment posting failed, please try again later.',
            ];
        }
    }

    /**
     * Deletes all payments for the Health quote (splits, logs, parent/child rows) and reverts lead to Application Pending.
     * Call only after {@see HealthQuoteService::canBypassPlanLock()} is true for the same quote and payments.
     */
    public function resetHealthManagePayments($quote, string $reason): void
    {
        DB::transaction(function () use ($quote, $reason) {
            $oldQuoteStatus = $quote->quote_status_id;

            $paymentCodes = $quote->payments()->pluck('code')->all();

            foreach ($paymentCodes as $paymentCode) {
                $this->deleteHealthPaymentCascade($paymentCode);
            }

            $quote->update([
                'quote_status_id' => QuoteStatusEnum::ApplicationPending,
                'payment_status_id' => null,
                'is_quote_locked' => false,
                'transaction_approved_at' => null,
                'reason_for_reset' => $reason,
            ]);

            if ($oldQuoteStatus !== null && $quote->quote_status_id != $oldQuoteStatus) {
                QuoteStatusLog::create([
                    'quote_type_id' => QuoteTypeId::Health,
                    'quote_request_id' => $quote->id,
                    'current_quote_status_id' => $quote->quote_status_id,
                    'previous_quote_status_id' => $oldQuoteStatus,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }

            LoggerService::info('Reset manage payments: payments removed and quote reverted to Application Pending', [
                'quote_code' => $quote->code,
                'reason' => $reason,
            ]);
        });
    }

    private function deleteHealthPaymentCascade(string $paymentCode): void
    {
        $paymentSplits = PaymentSplits::where('code', $paymentCode)->get();
        foreach ($paymentSplits as $paymentSplit) {
            $paymentSplit->documents()->forceDelete();
        }
        PaymentSplits::where('code', $paymentCode)->delete();
        PaymentStatusHistory::where('payment_code', $paymentCode)->delete();
        PaymentStatusLog::where('payment_code', $paymentCode)->delete();
        Payment::where('code', $paymentCode)->delete();
    }
}
