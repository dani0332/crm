<?php

namespace App\Services;

use App\Enums\DiscountTypeEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;

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
        $infoMessage = 'Quote Code: '.$payment->code;
        $priceWithVat = round($quoteObject->price_with_vat, 2);
        $capturedAmount = $payment->captured_amount;
        $discountValue = $payment->discount_value;
        $totalPaymentAmount = $capturedAmount + $discountValue;
        $initialDifference = $priceWithVat - $totalPaymentAmount;
        $difference = round($initialDifference, 2);

        $infoMessage .= 'CA: '.$capturedAmount.' DV: '.$discountValue.' TA: '.$totalPaymentAmount.' ';
        $infoMessage .= 'ID: '.$difference.' ';

        info($infoMessage);

        $this->setPaymentStatusBasedOnPrice($priceWithVat, $payment, $difference);

        $payment->total_price = $priceWithVat;
        $this->setTotalAmount($payment);

        if (! $isCreditCardEnabled && $payment->payment_methods_code == PaymentMethodsEnum::CreditCard && $payment->isInsurerPayment() && ! in_array($payment->payment_status_id, [PaymentStatusEnum::PAID, PaymentStatusEnum::AUTHORISED])) {
            $payment->payment_methods_code = PaymentMethodsEnum::InsurerPayment;
        }

        if ($payment->isDirty()) {
            $payment->save();
        }
    }

    /**
     * This method will set payment status in payment table
     * This method trigger when policy details section update
     */
    public function setPaymentStatusBasedOnPrice($priceWithVat, $payment, $difference): void
    {
        if ($payment->payment_methods_code != PaymentMethodsEnum::CreditApproval) {
            $captureAndDiscount = round(($payment->captured_amount + $payment->discount_value), 2);
            // If status is partially paid & total price is less than price with vat then set status to partially paid
            if (in_array($payment->payment_status_id, [PaymentStatusEnum::PAID, PaymentStatusEnum::AUTHORISED]) && $payment->total_price < $priceWithVat) {
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
        info('Quote Code: '.$payment->code.' Updating TA frequency is : '.$payment->frequency.' and payment_status_id: '.$payment->payment_status_id);
        if ($payment && $payment->frequency == PaymentFrequency::UPFRONT && in_array($payment->payment_status_id, [PaymentStatusEnum::PAID, PaymentStatusEnum::NEW, PaymentStatusEnum::OVERDUE])) {
            $totalPrice = $payment->total_price;
            $discountValue = $payment->discount_value;
            $totalAmount = $totalPrice - $discountValue;
            info('Quote Code: '.$payment->code.' updateTotalAmount - totalPrice: '.$totalPrice.', discountValue: '.$discountValue.', totalAmount: '.$totalAmount);
            $payment->total_amount = $totalAmount;
        }
    }
}
