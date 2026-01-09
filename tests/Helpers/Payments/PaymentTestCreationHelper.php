<?php

namespace Tests\Helpers\Payments;

use App\Models\CarQuote;
use App\Models\Payment;
use App\Models\PaymentSplits;

/**
 * Payment test creation helper.
 */
class PaymentTestCreationHelper
{
    /**
     * Create a payment via factory with CarQuote and refresh to apply observer-calculated VAT.
     */
    public static function createPayment(CarQuote $carQuote): Payment
    {
        // Create payment using factory with CarQuote
        // This will trigger PaymentObserver which calculates VAT
        $payment = Payment::factory()->createForSqlite($carQuote);

        // Refresh payment to get latest values from database (including observer updates)
        $payment->refresh();

        return $payment;
    }

    /**
     * Create a payment split using factory with Payment.
     */
    public static function createPaymentSplit(Payment $payment, array $attributes = []): PaymentSplits
    {
        // Create payment split using factory with Payment
        // This will trigger PaymentSplitsObserver which calculates VAT
        $paymentSplit = PaymentSplits::factory()->createForSqlite($payment, $attributes);

        // Refresh payment split to get latest values from database (including observer updates)
        $paymentSplit->refresh();

        return $paymentSplit;
    }

    /**
     * Create both payment and payment split in one call.
     */
    public static function createPaymentWithSplit(CarQuote $carQuote, array $paymentSplitAttributes = []): array
    {
        // Create payment using factory with CarQuote
        $payment = self::createPayment($carQuote);

        // Create payment split using factory with Payment
        $paymentSplit = self::createPaymentSplit($payment, $paymentSplitAttributes);

        return [
            'payment' => $payment,
            'paymentSplit' => $paymentSplit,
        ];
    }

    /**
     * Create a payment and ensure captured_amount is initialized to 0 if null.
     */
    public static function createPaymentWithCapturedAmount(CarQuote $carQuote): Payment
    {
        $payment = self::createPayment($carQuote);

        // Ensure captured_amount is initialized to 0 if null
        if ($payment->captured_amount === null) {
            $payment->update(['captured_amount' => 0]);
            $payment->refresh();
        }

        return $payment;
    }

    /**
     * Create payment with split and ensure captured_amount is initialized.
     */
    public static function createPaymentWithSplitAndCapturedAmount(CarQuote $carQuote, array $paymentSplitAttributes = []): array
    {
        // Create payment with captured_amount initialized
        $payment = self::createPaymentWithCapturedAmount($carQuote);

        // Create payment split using factory with Payment
        $paymentSplit = self::createPaymentSplit($payment, $paymentSplitAttributes);

        return [
            'payment' => $payment,
            'paymentSplit' => $paymentSplit,
        ];
    }
}
