<?php

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypes;
use App\Models\Payment;
use App\Models\PaymentSplits;
use Database\Factories\ApplicationStorageFactory;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\Payments\PaymentTestDataHelper;
use Tests\Helpers\Payments\PaymentTestAssertionHelper;
use Tests\Helpers\Payments\PaymentTestCreationHelper;
use Tests\Helpers\Payments\PaymentTestPayloadHelper;
use Tests\Helpers\Payments\PaymentTestQueryHelper;
use Tests\Helpers\TestSchemaCreator;

// Will run for each test 
beforeEach(function () {
    // Initialize test database schema
    TestSchemaCreator::createMinimalSchema();
    
    // Create VAT_VALUE record using factory
   ApplicationStorageFactory::createVatValueForSqlite('5');

    // Set up authenticated user
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
    
    // Set up payment-related permissions (required for payment approval/decline operations)
    PaymentTestDataHelper::setupPaymentPermissions($this->user);
    
    // Set up test data: InsuranceProvider, CarPlan, and CarQuote
    // This helper method creates all necessary test data for payment testing
    $testData = PaymentTestDataHelper::setupTestData();
    // Assign test data to test properties for easy access
    $this->insuranceProvider = $testData['insuranceProvider'];
    $this->carPlan = $testData['carPlan'];
    $this->carQuote = $testData['carQuote'];
    $this->quoteCode = $testData['quoteCode'];
    $this->quoteUuid = $testData['quoteUuid'];
});

test('payment and payment split can be created using factories', function () {
    // ============================================
    // 1. ARRANGE: Prepare test data
    // ============================================
    // No additional arrangement needed - data is prepared in beforeEach
    
    // ============================================
    // 2. ACT: Create payment and payment split using factories
    // ============================================
    
    // Create payment and payment split using helper method
    // This handles factory creation, observer triggers, and refresh operations
    $createdEntities = PaymentTestCreationHelper::createPaymentWithSplit($this->carQuote);
    $createdPayment = $createdEntities['payment'];
    $createdPaymentSplit = $createdEntities['paymentSplit'];
    
    // ============================================
    // 3- ASSERT: Verify the results
    // ============================================
    
    // Assert payment was created correctly
    PaymentTestAssertionHelper::assertPaymentCreatedCorrectly(
        payment: $createdPayment,
        carQuote: $this->carQuote,
        expectedPlanId: $this->carPlan->id,
        expectedInsuranceProviderId: $this->insuranceProvider->id,
        expectedUserId: $this->user->id
    );
    
    // Assert payment split was created correctly
    PaymentTestAssertionHelper::assertPaymentSplitCreatedCorrectly(
        paymentSplit: $createdPaymentSplit,
        payment: $createdPayment,
        expectedAmount: (float) $this->carQuote->premium
    );
    
    // Assert that observers ran successfully (VAT calculations)
    PaymentTestAssertionHelper::assertObserversRanSuccessfully(
        payment: $createdPayment,
        paymentSplit: $createdPaymentSplit
    );
});

test('payment should be created via endpoint', function () {
    // ============================================
    // 1-ARRANGE: Prepare test data and payload
    // ============================================
    
    // Build the request payload using helper method
    // This extracts data from factories and combines with CarQuote data
    $requestPayload = PaymentTestPayloadHelper::buildPaymentCreationPayload(
        carQuote: $this->carQuote,
        planId: $this->carPlan->id,
        insuranceProviderId: $this->insuranceProvider->id
    );
    
    // ============================================
    // 2- ACT: Execute the endpoint request
    // ============================================
    
    // Make POST request to payment creation endpoint using route name
    $response = $this->post(route('payment-create', ['quoteType' => QuoteTypes::CAR->value]), $requestPayload);
    
    // ============================================
    // 3- ASSERT: Verify the results
    // ============================================
    
    // Assert that the endpoint returned a successful redirect response
    $response->assertStatus(302);
    
    // Retrieve the created payment from database
    $createdPayment = PaymentTestQueryHelper::getPaymentByQuoteCode($this->carQuote->code);
    
    // Assert payment exists and was created correctly
    expect($createdPayment)->not->toBeNull();
    PaymentTestAssertionHelper::assertPaymentCreatedCorrectly(
        payment: $createdPayment,
        carQuote: $this->carQuote,
        expectedPlanId: $this->carPlan->id,
        expectedInsuranceProviderId: $this->insuranceProvider->id,
        expectedUserId: $this->user->id
    );
    
    // Retrieve the created payment split from database
    $createdPaymentSplit = PaymentTestQueryHelper::getPaymentSplitByCodeAndSerial(
        paymentCode: $createdPayment->code,
        srNo: 1
    );
    
    // Assert payment split exists and was created correctly
    expect($createdPaymentSplit)->not->toBeNull();
    PaymentTestAssertionHelper::assertPaymentSplitCreatedCorrectly(
        paymentSplit: $createdPaymentSplit,
        payment: $createdPayment,
        expectedAmount: (float) $this->carQuote->premium
    );
    
    // Assert that observers ran successfully (VAT calculations)
    PaymentTestAssertionHelper::assertObserversRanSuccessfully(
        payment: $createdPayment,
        paymentSplit: $createdPaymentSplit
    );
});

test('payment should be updated via endpoint', function () {
    // ============================================
    // 1. ARRANGE: Create payment and payment split using factories
    // ============================================
    // First, create payment and payment split using factories
    // This simulates an existing payment that needs to be updated
    
    // Create payment and payment split using helper methods
    // This will trigger observers which calculate VAT
    $createdEntities = PaymentTestCreationHelper::createPaymentWithSplit($this->carQuote);
    $existingPayment = $createdEntities['payment'];
    $existingPaymentSplit = $createdEntities['paymentSplit'];
    
    // Verify initial state - payment should have 1 split (upfront payment)
    expect($existingPayment)->not->toBeNull();
    expect($existingPaymentSplit)->not->toBeNull();
    
    // Build the update request payload using helper method
    // This payload will update payment to have 2 splits instead of 1
    $updatePayload = PaymentTestPayloadHelper::buildPaymentUpdatePayload(
        carQuote: $this->carQuote,
        payment: $existingPayment
    );

    // ============================================
    // 2. ACT: Execute the update endpoint request
    // ============================================
    
    // Make POST request to payment update endpoint using route name
    $response = $this->post("/payments/" . QuoteTypes::CAR->value . "/update-new", $updatePayload);
   
    // ============================================
    // 3. ASSERT: Verify the results
    // ============================================

    // Assert that the endpoint returned a successful redirect response
    $response->assertStatus(302);

    // Retrieve the updated payment from database
    $updatedPayment = PaymentTestQueryHelper::getPaymentByQuoteCode($this->carQuote->code);
    
    // Assert payment exists and was updated correctly
    expect($updatedPayment)->not->toBeNull();
    PaymentTestAssertionHelper::assertPaymentUpdatedCorrectly(
        payment: $updatedPayment,
        carQuote: $this->carQuote,
        expectedPlanId: $this->carPlan->id,
        expectedInsuranceProviderId: $this->insuranceProvider->id,
        expectedUserId: $this->user->id
    );

    // Retrieve the updated payment split from database (should still be sr_no = 1)
    $updatedPaymentSplit = PaymentTestQueryHelper::getPaymentSplitByCodeAndSerial(
        paymentCode: $updatedPayment->code,
        srNo: 1
    );
    
    // Assert payment split exists and was updated correctly
    expect($updatedPaymentSplit)->not->toBeNull();
    PaymentTestAssertionHelper::assertPaymentSplitUpdatedCorrectly(
        paymentSplit: $updatedPaymentSplit,
        payment: $updatedPayment
    );
});

test('payment split validates presence and format of insurer receipt number', function () {
    // ============================================
    // 1. ARRANGE: Create payment splits for testing
    // ============================================
    // Create payment using helper method
    $payment = PaymentTestCreationHelper::createPayment($this->carQuote);
    
    // Create a payment split with an existing receipt number for case 3
    $existingReceiptNumber = 'INS-REC-EXISTING';
    PaymentTestCreationHelper::createPaymentSplit($payment, [
        'insurer_receipt_number' => $existingReceiptNumber,
    ]);

    // Create another payment split without insurer_receipt_number for testing updates
    PaymentTestCreationHelper::createPaymentSplit($payment, [
        'insurer_receipt_number' => null,
    ]);

    $quoteType = QuoteTypes::CAR->value;

    // ============================================
    // CASE 1: Test null validation error
    // ============================================
    $response = $this->postJson(
        '/payments/' . $quoteType . '/check-insurer-receipt-number',
        ['insurer_receipt_number' => null]
    );
    PaymentTestAssertionHelper::assertInsurerReceiptNumberNullValidationError($response);

    // ============================================
    // CASE 2: Test with non-existent receipt number (should show "not exists")
    // ============================================
    $nonExistentReceipt = 'INS-REC-NONEXISTENT';
    $response = $this->postJson(
        '/payments/' . $quoteType . '/check-insurer-receipt-number',
        ['insurer_receipt_number' => $nonExistentReceipt]
    );
    PaymentTestAssertionHelper::assertInsurerReceiptNumberDoesNotExist($response);

    // ============================================
    // CASE 3: Test with existing receipt number (should show "exists")
    // ============================================
    $response = $this->postJson(
        '/payments/' . $quoteType . '/check-insurer-receipt-number',
        ['insurer_receipt_number' => $existingReceiptNumber]
    );
    PaymentTestAssertionHelper::assertInsurerReceiptNumberAlreadyExists($response);
});

test('payment should be approved via endpoint', function () {
    // ============================================
    // 1. ARRANGE: Create payment and payment split using factories
    // ============================================
    // First, create payment and payment split using factories
    // This simulates an existing payment that needs to be approved
    
    // Disable Sage API for testing to avoid external API calls
    $db = \Illuminate\Support\Facades\DB::connection('sqlite');
    $db->table('application_storage')->updateOrInsert(
        ['key_name' => ApplicationStorageEnums::SAGE_ENABLED],
        ['value' => '0', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]
    );
    
    // Create payment and payment split using helper methods
    // This will trigger observers which calculate VAT and handle captured_amount initialization
    $createdEntities = PaymentTestCreationHelper::createPaymentWithSplitAndCapturedAmount($this->carQuote);
    $existingPayment = $createdEntities['payment'];
    $existingPaymentSplit = $createdEntities['paymentSplit'];
    
    // Verify initial state - payment should have 1 split (upfront payment)
    expect($existingPayment)->not->toBeNull();
    expect($existingPaymentSplit)->not->toBeNull();
    
    // Store initial captured_amount before approval
    $initialCapturedAmount = (float) ($existingPayment->captured_amount ?? 0);
    $collectionAmount = (float) $existingPaymentSplit->payment_amount;
    
    // Build the approval request payload using helper method
    $approvePayload = PaymentTestPayloadHelper::buildApprovePaymentPayload(
        carQuote: $this->carQuote,
        paymentSplit: $existingPaymentSplit
    );

    // ============================================
    // 2. ACT: Execute the approval endpoint request
    // ============================================
    
    // Make POST request to payment approval endpoint
    $response = $this->post("/payments/" . QuoteTypes::CAR->value . "/split-payment-approve-decline", $approvePayload);

    // ============================================
    // 3. ASSERT: Verify the results
    // ============================================

    // Assert that the endpoint returned a successful redirect response
    // This will fail with helpful error message if status is not 302
    $response->assertStatus(302);

    // Retrieve the updated payment from database
    $updatedPayment = PaymentTestQueryHelper::getPaymentByQuoteCode($this->carQuote->code);
    // Assert payment exists
    expect($updatedPayment)->not->toBeNull();
    
    // Refresh to get latest values
    $updatedPayment->refresh();

    // Assert captured_amount was incremented correctly
    PaymentTestAssertionHelper::assertPaymentCapturedAmountUpdatedCorrectly(
        payment: $updatedPayment,
        expectedCapturedAmount: $initialCapturedAmount + $collectionAmount
    );

    // Retrieve the approved payment split from database
    $approvedPaymentSplit = PaymentTestQueryHelper::getPaymentSplitByCodeAndSerial(
        paymentCode: $updatedPayment->code,
        srNo: 1
    );
    
    // Refresh to get latest values
    $approvedPaymentSplit->refresh();

    // Assert payment split exists and was approved correctly
    expect($approvedPaymentSplit)->not->toBeNull();
    PaymentTestAssertionHelper::assertPaymentSplitApprovedCorrectly(
        paymentSplit: $approvedPaymentSplit,
        expectedCollectionAmount: $collectionAmount,
        expectedInsurerReceiptNumber: $approvePayload['insurer_receipt_number'],
        expectedUserId: $this->user->id,
        actualAmount: $approvePayload['actual_amount']
    );
});