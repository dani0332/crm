<?php

namespace Tests\Helpers\Payments;

use App\Enums\CollectionTypeEnum;
use App\Enums\PaymentCollectionTypeEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Models\CarPlan;
use App\Models\CarQuote;
use App\Models\InsuranceProvider;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

    /**
     * Assert that a payment split was approved correctly.
     *
     * @param PaymentSplits $paymentSplit The payment split to validate
     * @param float $expectedCollectionAmount The expected collection amount
     * @param string|null $expectedBankReferenceNumber The expected bank reference number (nullable)
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
            ->and($paymentSplit->payment_allocation_status)->toBe(\App\Enums\PaymentAllocationStatus::NOT_ALLOCATED)
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
            ->and($payment->payment_allocation_status)->toBe(\App\Enums\PaymentAllocationStatus::NOT_ALLOCATED);
    }

    public static function buildApprovePaymentPayload(CarQuote $carQuote, PaymentSplits $paymentSplit): array {
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
            'insurer_receipt_number' => 'INS-REC-' . $paymentSplit->code,
            'actual_amount' => (float) $paymentSplit->payment_amount,
            'approved_document_model' => [],
            'declined_reason' => null,
            'declined_custom_reason' => null,
            'send_update_id' => null,
        ];

        return $approvePayload;
    }

    /**
     * Set up payment-related permissions for testing.
     * Creates and assigns required permissions to the Admin role and user.
     * This includes permissions needed for payment approval/decline operations.
     *
     * @param User $user The user to assign permissions to
     * @return void
     */
    public static function setupPaymentPermissions(User $user): void
    {
        // Required permissions for payment operations
        // These permissions are checked in SplitPaymentUpdateRequest validation
        $permissions = [
            PermissionsEnum::PAYMENT_VERIFICATION_COLLECTED_BY_INSURER, // Required for insurer collection type approval
            PermissionsEnum::PAYMENT_VERIFICATION_COLLECTED_BY_BROKER,  // Required for broker collection type approval
            PermissionsEnum::INPL_APPROVER,                              // Required for INPL payment method approval
        ];

        // Get or create Admin role on SQLite connection
        $adminRole = Role::on('sqlite')->firstOrCreate(
            ['name' => \App\Enums\RolesEnum::Admin, 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // Create and assign each permission
        foreach ($permissions as $permissionName) {
            // Create permission on SQLite connection
            $permission = Permission::on('sqlite')->firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );

            // Assign permission to Admin role
            $adminRole->givePermissionTo($permission);


            // Assign permission directly to user as well
            $user->refresh();
            $user->givePermissionTo($permission);
        }

        // Clear permission cache to ensure permissions are available immediately
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Refresh user to ensure permissions are loaded
        $user->refresh();
    }
}

