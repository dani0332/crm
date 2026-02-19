<?php

namespace App\Services;

use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\V2\CentralController;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Repositories\SendUpdateLogRepository;
use App\Services\Logger\LoggerService;
use Illuminate\Http\Request;

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
        $priceWithVat = round($quoteObject->price_with_vat, 2);
        $this->setPaymentStatusBasedOnPrice($priceWithVat, $payment);

        $payment->total_price = $priceWithVat;
        $payment->price_vat_applicable = $quoteObject->price_vat_applicable + $quoteObject->price_vat_not_applicable;
        $payment->price_vat = $quoteObject->vat ?? $quoteObject->total_vat_amount ?? 0;

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
            $captureAndDiscount = round(($payment->captured_amount + $payment->discount_value), 2);
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
            $totalPrice = $payment->total_price;
            $discountValue = $payment->discount_value;
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
}
