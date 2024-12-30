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
    public function processMasterPayment($payment, $quoteObject)
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
        if ($payment->system_adjusted_discount != null) {
            $difference += $payment->system_adjusted_discount;
            $infoMessage .= 'SAD: '.$payment->system_adjusted_discount.' DASA '.$difference;
        }
        $this->handleSystemAdjustedDiscount($payment, $difference, $initialDifference);
        info($infoMessage);

        $this->setPaymentStatusBasedOnPrice($priceWithVat, $payment, $difference);

        $payment->total_price = $priceWithVat;
        $this->setTotalAmount($payment);

        if ($payment->isDirty()) {
            $payment->save();
        }
    }

    private function handleSystemAdjustedDiscount($payment, $difference, $initialDifference)
    {
        // Case 1 if difference is less than 1 and greater than 0 else set total price to price with vat
        if ($difference <= 0.99 && $difference > 0) {
            $payment->system_adjusted_discount = $difference;
            // If condition to check if discount value is not null & add difference to it else set difference as discount value
            if ($payment->discount_value != null) {
                $payment->discount_value += $initialDifference;
            } else {
                $payment->discount_value = $difference;
                $payment->discount_type = DiscountTypeEnum::SYSTEM_ADJUSTED_DISCOUNT;
            }
        }
        // Case 2 if difference is greater than 0.99 and system adjusted discount is greater than 0 then subtract system adjusted discount from discount value
        elseif (($difference > 0.99 || $difference == 0) && $payment->system_adjusted_discount > 0) {
            $payment->discount_value -= $payment->system_adjusted_discount;
            $payment->system_adjusted_discount = 0;
            if ($payment->discount_type == DiscountTypeEnum::SYSTEM_ADJUSTED_DISCOUNT) {
                $payment->discount_type = null;
            }
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
            if ($payment->payment_status_id === PaymentStatusEnum::PAID && $payment->total_price < $priceWithVat && ($difference > 0.99)) {
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
