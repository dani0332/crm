<?php

namespace Tests\Helpers\Payments;

use App\Models\CarQuote;
use App\Models\Payment;
use App\Models\PaymentSplits;

/**
 * Helper class for creating payment-related entities in tests.
 * Handles creation of payments and payment splits with proper refresh and observer handling.
 */
class PaymentTestCreationHelper
{
    /**
     * Create a payment using factory with CarQuote.
     * This will trigger PaymentObserver which calculates VAT.
     * The payment is automatically refreshed to get latest values from database.
     *
     * @param CarQuote $carQuote The car quote to create payment for
     * @return Payment The created and refreshed payment
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
     * This will trigger PaymentSplitsObserver which calculates VAT.
     * The payment split is automatically refreshed to get latest values from database.
     *
     * @param Payment $payment The parent payment
     * @param array $attributes Optional additional attributes for the payment split
     * @return PaymentSplits The created and refreshed payment split
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
     * This is a convenience method that creates payment first, then payment split.
     * Both entities are automatically refreshed.
     *
     * @param CarQuote $carQuote The car quote to create payment for
     * @param array $paymentSplitAttributes Optional additional attributes for the payment split
     * @return array Returns array with 'payment' and 'paymentSplit' keys
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
     * Useful for tests that need to track captured amounts.
     *
     * @param CarQuote $carQuote The car quote to create payment for
     * @return Payment The created and refreshed payment with captured_amount initialized
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
     * This is a convenience method for approval tests.
     *
     * @param CarQuote $carQuote The car quote to create payment for
     * @param array $paymentSplitAttributes Optional additional attributes for the payment split
     * @return array Returns array with 'payment' and 'paymentSplit' keys
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

