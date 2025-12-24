<?php

namespace Tests\Helpers\Payments;

use App\Enums\PaymentAllocationStatus;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentCollectionTypeEnum;
use App\Models\CarQuote;
use App\Models\Payment;
use App\Models\PaymentSplits;

/**
 * Helper class for payment test assertions.
 * Contains all assertion methods for validating payment-related operations.
 */
class PaymentTestAssertionHelper
{
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
     * Assert that a payment was updated correctly in the database.
     *
     * @param Payment $payment The payment to validate
     * @param CarQuote $carQuote The expected car quote
     * @param int $expectedPlanId The expected plan ID
     * @param int $expectedInsuranceProviderId The expected insurance provider ID
     * @param int $expectedUserId The expected user ID who updated the payment
     * @return void
     */
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

    /**
     * Assert that a payment split was updated correctly in the database.
     *
     * @param PaymentSplits $paymentSplit The payment split to validate
     * @param Payment $payment The parent payment
     * @return void
     */
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

    /**
     * Assert that a payment split was approved correctly.
     *
     * @param PaymentSplits $paymentSplit The payment split to validate
     * @param float $expectedCollectionAmount The expected collection amount
     * @param string|null $expectedInsurerReceiptNumber The expected insurer receipt number (nullable)
     * @param int $expectedUserId The expected user ID who approved the payment
     * @param float|null $actualAmount The actual amount (used to determine if status should be PARTIALLY_PAID)
     * @return void
     */
    public static function assertPaymentSplitApprovedCorrectly(
        PaymentSplits $paymentSplit,
        float $expectedCollectionAmount,
        ?string $expectedInsurerReceiptNumber,
        int $expectedUserId,
        ?float $actualAmount = null
    ): void {
        // Determine expected payment status
        $expectedPaymentStatus = PaymentStatusEnum::PAID;
        if ($actualAmount && $actualAmount > $expectedCollectionAmount) {
            $expectedPaymentStatus = PaymentStatusEnum::PARTIALLY_PAID;
        }

        expect((float) $paymentSplit->collection_amount)->toBe((float) $expectedCollectionAmount)
            ->and($paymentSplit->payment_status_id)->toBe($expectedPaymentStatus)
            ->and($paymentSplit->payment_allocation_status)->toBe(PaymentAllocationStatus::NOT_ALLOCATED)
            ->and($paymentSplit->verified_at)->not->toBeNull()
            ->and($paymentSplit->verified_by)->toBe($expectedUserId);

        if ($expectedInsurerReceiptNumber !== null) {
            expect($paymentSplit->insurer_receipt_number)->toBe($expectedInsurerReceiptNumber);
        }
    }

    /**
     * Assert that a master payment was updated correctly after split payment approval.
     *
     * @param Payment $payment The payment to validate
     * @param float $expectedCapturedAmount The expected total captured_amount after approval
     * @return void
     */
    public static function assertPaymentCapturedAmountUpdatedCorrectly(
        Payment $payment,
        float $expectedCapturedAmount
    ): void {
        expect((float) $payment->captured_amount)->toBe((float) $expectedCapturedAmount)
            ->and($payment->payment_allocation_status)->toBe(PaymentAllocationStatus::NOT_ALLOCATED);
    }

    /**
     * Assert that insurer receipt number validation returns a null validation error.
     *
     * @param \Illuminate\Testing\TestResponse $response The HTTP response from the validation endpoint
     * @return void
     */
    public static function assertInsurerReceiptNumberNullValidationError($response): void
    {
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['insurer_receipt_number']);
    }

    /**
     * Assert that insurer receipt number validation returns "does not exist" response.
     *
     * @param \Illuminate\Testing\TestResponse $response The HTTP response from the validation endpoint
     * @return void
     */
    public static function assertInsurerReceiptNumberDoesNotExist($response): void
    {
        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Receipt number does not exist',
        ]);
    }

    /**
     * Assert that insurer receipt number validation returns "already exists" response.
     *
     * @param \Illuminate\Testing\TestResponse $response The HTTP response from the validation endpoint
     * @return void
     */
    public static function assertInsurerReceiptNumberAlreadyExists($response): void
    {
        $response->assertStatus(200);
        $response->assertJson([
            'status' => false,
            'message' => 'Receipt number already exists',
        ]);
    }
}

