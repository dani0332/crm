<?php

namespace Tests\Helpers\Payments;

use App\Enums\PaymentCollectionTypeEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
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
     * @param  CarQuote  $carQuote  The car quote to create payment for
     * @param  int  $planId  The plan ID
     * @param  int  $insuranceProviderId  The insurance provider ID
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

    /**
     * Build payment approval request payload for testing payment approval endpoint.
     *
     * @param  CarQuote  $carQuote  The car quote
     * @param  PaymentSplits  $paymentSplit  The payment split to approve
     * @return array The complete request payload matching endpoint structure
     */
    public static function buildApprovePaymentPayload(CarQuote $carQuote, PaymentSplits $paymentSplit): array
    {
        $approvePayload = [
            'splitPaymentId' => $paymentSplit->id,
            'is_approved' => true,
            'is_declined' => false,
            'modelType' => QuoteTypes::CAR->value,
            'quote_id' => $carQuote->id,
            'plan_id' => $carQuote->plan_id,
            'customer_id' => $carQuote->customer_id,
            'collection_amount' => (float) $paymentSplit->payment_amount, // Ensure numeric, not string
            'collection_type' => PaymentCollectionTypeEnum::INSURER,
            'bank_reference_number' => 'TEST-BANK-REF-123',
            'insurer_receipt_number' => 'INS-REC-'.$paymentSplit->code,
            'actual_amount' => (float) $paymentSplit->payment_amount,
            'approved_document_model' => [],
            'declined_reason' => null,
            'declined_custom_reason' => null,
            'send_update_id' => null,
        ];

        return $approvePayload;
    }

    /**
     * Build payment update request payload for testing payment update endpoint.
     * This payload will update payment to have 2 splits instead of 1.
     *
     * @param  CarQuote  $carQuote  The car quote
     * @param  Payment  $payment  The existing payment to update
     * @return array The complete request payload matching endpoint structure
     */
    public static function buildPaymentUpdatePayload(CarQuote $carQuote, Payment $payment): array
    {
        $premium = (float) $carQuote->premium;
        $paymentNo = 2;
        $splitPrice = round($premium / $paymentNo, 2);

        $updatePayload = [
            'modelType' => QuoteTypes::CAR->value,
            'quote_id' => $carQuote->id,
            'plan_id' => $payment->plan_id,
            'captured_amount' => '',
            'insurance_provider_id' => $payment->insurance_provider_id,
            'new_payment_structure' => true,
            'send_update_id' => null,
            'payment' => [
                'collection_type' => PaymentCollectionTypeEnum::INSURER,
                'payment_methods' => PaymentMethodsEnum::MultiplePayment,
                'reference' => '',
                'payment_no' => $paymentNo,
                'frequency' => PaymentFrequency::SPLIT_PAYMENTS,
                'credit_approval' => '',
                'discount' => '',
                'discount_reason' => '',
                'custom_reason' => null,
                'discount_custom_reason' => null,
                'collection_date' => now(),
                'notes' => null,
                'total_amount' => $premium,
                'total_price' => $premium,
                'discount_value' => 0,
                'payment_splits' => [
                    [
                        'sr_no' => 1,
                        'payment_method' => PaymentMethodsEnum::CreditCard,
                        'payment_amount' => $splitPrice,
                        'due_date' => now(),
                        'collection_amount' => null,
                        'document_detail' => [],
                        'discount_documents' => [],
                    ],
                    [
                        'sr_no' => 2,
                        'payment_method' => PaymentMethodsEnum::InsurerPayment,
                        'payment_amount' => $splitPrice,
                        'due_date' => now(),
                        'collection_amount' => null,
                    ],
                ],
            ],
            'paymentCode' => $payment->code,
            'trashedFilesModal' => [],
            'isPaymentLocked' => false,
            'isPolicyIssuanceDiscount' => false,
            'isPaidEditable' => false,
        ];

        return $updatePayload;
    }
}
