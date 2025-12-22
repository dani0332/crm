<?php

namespace Tests\Helpers;

use App\Enums\PaymentCollectionTypeEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\CarPlan;
use App\Models\CarQuote;
use App\Models\InsuranceProvider;
use App\Models\Payment;
use App\Models\PaymentSplits;

class PaymentTestHelper
{
    /**
     * Set up test data: InsuranceProvider, CarPlan, and CarQuote.
     * Creates all necessary test data for payment testing.
     *
     * @param string $quoteCode Optional quote code (default: 'CAR-ABCDEF12345')
     * @param string $quoteUuid Optional quote UUID (default: 'ABCDEF12345')
     * @return array Returns array with 'insuranceProvider', 'carPlan', 'carQuote', 'quoteCode', 'quoteUuid'
     */
    public static function setupTestData(
        string $quoteCode = 'CAR-ABCDEF12345',
        string $quoteUuid = 'ABCDEF12345'
    ): array {
        // Create InsuranceProvider using model-based creation
        $providerAttributes = InsuranceProvider::factory()->definition();
        $insuranceProvider = InsuranceProvider::forceCreate($providerAttributes);
        
        // Create CarPlan linked to the InsuranceProvider using model-based creation
        $planFactory = CarPlan::factory();
        $planAttributes = $planFactory->definition();
        // Replace factory relationship with actual provider ID
        $planAttributes['provider_id'] = $insuranceProvider->id;
        $carPlan = CarPlan::forceCreate($planAttributes);
        
        // Create CarQuote using factory method that handles SQLite connection
        $carQuote = CarQuote::factory()->createForSqlite([
            'uuid' => $quoteUuid,
            'code' => $quoteCode,
            'insurance_provider_id' => $insuranceProvider->id,
            'plan_id' => $carPlan->id,
        ]);
        
        return [
            'insuranceProvider' => $insuranceProvider,
            'carPlan' => $carPlan,
            'carQuote' => $carQuote,
            'quoteCode' => $quoteCode,
            'quoteUuid' => $quoteUuid,
        ];
    }
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

    /**
     * Assert that a payment was created correctly in the database.
     *
     * @param Payment $payment The payment to validate
     * @param CarQuote $carQuote The expected car quote
     * @param int $expectedPlanId The expected plan ID
     * @param int $expectedInsuranceProviderId The expected insurance provider ID
     * @param int $expectedUserId The expected user ID who created the payment
     * @return void
     */
    public static function assertPaymentCreatedCorrectly(
        Payment $payment,
        CarQuote $carQuote,
        int $expectedPlanId,
        int $expectedInsuranceProviderId,
        int $expectedUserId
    ): void {
        expect($payment->code)->toBe($carQuote->code)
            ->and($payment->plan_id)->toBe($expectedPlanId)
            ->and($payment->insurance_provider_id)->toBe($expectedInsuranceProviderId)
            ->and((float) $payment->total_price)->toBe((float) $carQuote->premium)
            ->and((float) $payment->total_amount)->toBe((float) $carQuote->premium)
            ->and($payment->discount_value)->toBe(0)
            ->and($payment->created_by)->toBe($expectedUserId)
            ->and($payment->updated_by)->toBe($expectedUserId)
            ->and($payment->paymentable_id)->toBe($carQuote->id)
            ->and($payment->paymentable_type)->toBe(\App\Models\CarQuote::class);
    }

    /**
     * Assert that a payment split was created correctly in the database.
     *
     * @param PaymentSplits $paymentSplit The payment split to validate
     * @param Payment $payment The parent payment
     * @param float $expectedAmount The expected payment amount
     * @return void
     */
    public static function assertPaymentSplitCreatedCorrectly(
        PaymentSplits $paymentSplit,
        Payment $payment,
        float $expectedAmount
    ): void {
        expect($paymentSplit->code)->toBe($payment->code)
            ->and((float) $paymentSplit->payment_amount)->toBe($expectedAmount)
            ->and($paymentSplit->discount_value)->toBe(0)
            ->and($paymentSplit->sr_no)->toBe(1)
            ->and($paymentSplit->payment_method)->toBe(\App\Enums\PaymentMethodsEnum::InsurerPayment)
            ->and($paymentSplit->payment_status_id)->toBe(\App\Enums\PaymentStatusEnum::NEW);
    }

    /**
     * Assert that observers ran successfully by checking VAT fields.
     *
     * @param Payment $payment The payment to check
     * @param PaymentSplits $paymentSplit The payment split to check
     * @return void
     */
    public static function assertObserversRanSuccessfully(
        Payment $payment,
        PaymentSplits $paymentSplit
    ): void {
        expect($payment->price_vat_applicable)->not->toBeNull()
            ->and($payment->price_vat)->not->toBeNull()
            ->and((float) $payment->price_vat_applicable)->toBeGreaterThan(0)
            ->and((float) $payment->price_vat)->toBeGreaterThan(0)
            ->and($paymentSplit->price_vat_applicable)->not->toBeNull()
            ->and($paymentSplit->price_vat)->not->toBeNull()
            ->and((float) $paymentSplit->price_vat_applicable)->toBeGreaterThan(0)
            ->and((float) $paymentSplit->price_vat)->toBeGreaterThan(0);
    }

    /**
     * Retrieve payment from database by quote code.
     *
     * @param string $quoteCode The quote code to search for
     * @return Payment|null
     */
    public static function getPaymentByQuoteCode(string $quoteCode): ?Payment
    {
        return Payment::on('sqlite')
            ->where('code', $quoteCode)
            ->first();
    }

    /**
     * Retrieve payment split from database by payment code and serial number.
     *
     * @param string $paymentCode The payment code
     * @param int $srNo The serial number
     * @return PaymentSplits|null
     */
    public static function getPaymentSplitByCodeAndSerial(string $paymentCode, int $srNo = 1): ?PaymentSplits
    {
        return PaymentSplits::on('sqlite')
            ->where('code', $paymentCode)
            ->where('sr_no', $srNo)
            ->first();
    }

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
                        'discount_documents' => []
                    ],
                    [
                        'sr_no' => 2,
                        'payment_method' => PaymentMethodsEnum::InsurerPayment,
                        'payment_amount' => $splitPrice,
                        'due_date' => now(),
                        'collection_amount' => null,
                    ]
                ]
            ],
            'paymentCode' => $payment->code,
            'trashedFilesModal' => [],
            'isPaymentLocked' => false,
            'isPolicyIssuanceDiscount' => false,
            'isPaidEditable' => false
        ];

        return $updatePayload;  
    }

    public static function assertPaymentUpdatedCorrectly(
        Payment $payment,
        CarQuote $carQuote,
        int $expectedPlanId,
        int $expectedInsuranceProviderId,
        int $expectedUserId
    ): void {
        // Handle collection_date - it might be a string or Carbon instance
        $collectionDate = is_string($payment->collection_date) 
            ? \Carbon\Carbon::parse($payment->collection_date) 
            : $payment->collection_date;
        
        expect($payment->code)->toBe($carQuote->code)
            ->and($payment->total_payments)->toBe(2)
            ->and($payment->frequency)->toBe(PaymentFrequency::SPLIT_PAYMENTS)
            ->and($payment->collection_type)->toBe(PaymentCollectionTypeEnum::INSURER)
            ->and($payment->payment_methods_code)->toBe(PaymentMethodsEnum::MultiplePayment)
            ->and($collectionDate->toDateString())->toBe(now()->toDateString())
            ->and($payment->plan_id)->toBe($expectedPlanId)
            ->and($payment->insurance_provider_id)->toBe($expectedInsuranceProviderId)
            ->and((float) $payment->total_price)->toBe((float) $carQuote->premium)
            ->and((float) $payment->total_amount)->toBe((float) $carQuote->premium)
            ->and($payment->discount_value)->toBe(0)
            ->and($payment->created_by)->toBe($expectedUserId)
            ->and($payment->updated_by)->toBe($expectedUserId);
    }

    public static function assertPaymentSplitUpdatedCorrectly(
        PaymentSplits $paymentSplit,
        Payment $payment
    ): void {

        $amount = (float) $payment->total_price / 2;
        expect($paymentSplit->code)->toBe($payment->code)
            ->and((float) $paymentSplit->payment_amount)->toBe($amount)
            ->and($paymentSplit->discount_value)->toBe(0)
            ->and($paymentSplit->sr_no)->toBe(1)
            ->and($paymentSplit->payment_method)->toBe(PaymentMethodsEnum::CreditCard)
            ->and($paymentSplit->payment_status_id)->toBe(PaymentStatusEnum::NEW);
    }
}

