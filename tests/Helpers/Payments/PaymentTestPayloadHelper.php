<?php

namespace Tests\Helpers\Payments;

use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Models\Payment;
use App\Models\PaymentSplits;

/**
 * Helper class for building request payloads for payment tests.
 * Handles creation of payloads for payment creation, update, and approval endpoints.
 */
class PaymentTestPayloadHelper
{
    /**
     * Build payment request payload for testing payment creation endpoint.
     * Extracts data from factories and CarQuote to match the endpoint's expected structure.
     *
     * @param CarQuote $carQuote The car quote to create payment for
     * @param int $planId The plan ID
     * @param int $insuranceProviderId The insurance provider ID
     * @return array The complete request payload matching endpoint structure
     */
    public static function buildPaymentCreationPayload(
        CarQuote $carQuote,
        int $planId,
        int $insuranceProviderId
    ): array {
        // Get payment data structure from factory definition
        $paymentFactory = Payment::factory();
        $paymentData = $paymentFactory->definition();
        
        // Get payment split data structure from factory definition
        $paymentSplitFactory = PaymentSplits::factory();
        $paymentSplitData = $paymentSplitFactory->definition();
        
        // Build the complete payload matching the endpoint's expected structure
        return [
            'code' => $carQuote->code,
            'modelType' => QuoteTypes::CAR->value,
            'quote_id' => $carQuote->id,
            'plan_id' => $planId,
            'insurance_provider_id' => $insuranceProviderId,
            'captured_amount' => '',
            'send_update_id' => null,
            'new_payment_structure' => true, // Required for new payment structure validation
            'payment' => [
                'collection_type' => $paymentData['collection_type'],
                'payment_methods' => $paymentData['payment_methods_code'],
                'reference' => $paymentData['reference'] ?? '',
                'payment_no' => (string) $paymentData['total_payments'],
                'frequency' => $paymentData['frequency'],
                'credit_approval' => $paymentData['credit_approval'] ?? '',
                'discount' => $paymentData['discount_type'] ?? '',
                'collection_date' => $paymentData['collection_date']->toIso8601String(),
                'total_amount' => (string) $carQuote->premium,
                'total_price' => (string) $carQuote->premium,
                'discount_value' => $paymentData['discount_value'],
                'payment_splits' => [
                    [
                        'sr_no' => $paymentSplitData['sr_no'],
                        'payment_method' => $paymentSplitData['payment_method'],
                        'payment_amount' => (string) $carQuote->premium,
                        'due_date' => $paymentSplitData['due_date']->toIso8601String(),
                        'document_detail' => [],
                        'discount_documents' => [],
                    ],
                ],
            ],
        ];
    }
}

