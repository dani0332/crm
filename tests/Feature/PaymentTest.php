<?php

use App\Enums\QuoteTypes;
use Tests\Helpers\PaymentTestHelper;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

// Will run for each test 
beforeEach(function () {
    // Initialize test database schema
    TestSchemaCreator::createMinimalSchema();
    
    // Create VAT_VALUE record using factory
    \Database\Factories\ApplicationStorageFactory::createVatValueForSqlite('5');

    // Set up authenticated user
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
    
    // Set up test data: InsuranceProvider, CarPlan, and CarQuote
    // This helper method creates all necessary test data for payment testing
    $testData = PaymentTestHelper::setupTestData();
    
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
    
    // Create payment using factory with CarQuote
    // This will trigger PaymentObserver which calculates VAT
    $createdPayment = \App\Models\Payment::factory()->createForSqlite($this->carQuote);
    
    // Refresh payment to get latest values from database (including observer updates)
    $createdPayment->refresh();
    
    // Create payment split using factory with Payment
    // This will trigger PaymentSplitsObserver which calculates VAT
    $createdPaymentSplit = \App\Models\PaymentSplits::factory()->createForSqlite($createdPayment);
    
    // Refresh payment split to get latest values from database (including observer updates)
    $createdPaymentSplit->refresh();
    
    // ============================================
    // 3- ASSERT: Verify the results
    // ============================================
    
    // Assert payment was created correctly
    PaymentTestHelper::assertPaymentCreatedCorrectly(
        payment: $createdPayment,
        carQuote: $this->carQuote,
        expectedPlanId: $this->carPlan->id,
        expectedInsuranceProviderId: $this->insuranceProvider->id,
        expectedUserId: $this->user->id
    );
    
    // Assert payment split was created correctly
    PaymentTestHelper::assertPaymentSplitCreatedCorrectly(
        paymentSplit: $createdPaymentSplit,
        payment: $createdPayment,
        expectedAmount: (float) $this->carQuote->premium
    );
    
    // Assert that observers ran successfully (VAT calculations)
    PaymentTestHelper::assertObserversRanSuccessfully(
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
    $requestPayload = PaymentTestHelper::buildPaymentCreationPayload(
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
    $createdPayment = PaymentTestHelper::getPaymentByQuoteCode($this->carQuote->code);
    
    // Assert payment exists and was created correctly
    expect($createdPayment)->not->toBeNull();
    PaymentTestHelper::assertPaymentCreatedCorrectly(
        payment: $createdPayment,
        carQuote: $this->carQuote,
        expectedPlanId: $this->carPlan->id,
        expectedInsuranceProviderId: $this->insuranceProvider->id,
        expectedUserId: $this->user->id
    );
    
    // Retrieve the created payment split from database
    $createdPaymentSplit = PaymentTestHelper::getPaymentSplitByCodeAndSerial(
        paymentCode: $createdPayment->code,
        srNo: 1
    );
    
    // Assert payment split exists and was created correctly
    expect($createdPaymentSplit)->not->toBeNull();
    PaymentTestHelper::assertPaymentSplitCreatedCorrectly(
        paymentSplit: $createdPaymentSplit,
        payment: $createdPayment,
        expectedAmount: (float) $this->carQuote->premium
    );
    
    // Assert that observers ran successfully (VAT calculations)
    PaymentTestHelper::assertObserversRanSuccessfully(
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
    
    // Create payment using factory with CarQuote
    // This will trigger PaymentObserver which calculates VAT
    $existingPayment = \App\Models\Payment::factory()->createForSqlite($this->carQuote);
    
    // Refresh payment to get latest values from database (including observer updates)
    $existingPayment->refresh();
    
    // Create payment split using factory with Payment
    // This will trigger PaymentSplitsObserver which calculates VAT
    $existingPaymentSplit = \App\Models\PaymentSplits::factory()->createForSqlite($existingPayment);
    
    // Refresh payment split to get latest values from database (including observer updates)
    $existingPaymentSplit->refresh();
    
    // Verify initial state - payment should have 1 split (upfront payment)
    expect($existingPayment)->not->toBeNull();
    expect($existingPaymentSplit)->not->toBeNull();
    
    // Build the update request payload using helper method
    // This payload will update payment to have 2 splits instead of 1
    $updatePayload = PaymentTestHelper::buildPaymentUpdatePayload(
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
    $updatedPayment = PaymentTestHelper::getPaymentByQuoteCode($this->carQuote->code);
    
    // Assert payment exists and was updated correctly
    expect($updatedPayment)->not->toBeNull();
    PaymentTestHelper::assertPaymentUpdatedCorrectly(
        payment: $updatedPayment,
        carQuote: $this->carQuote,
        expectedPlanId: $this->carPlan->id,
        expectedInsuranceProviderId: $this->insuranceProvider->id,
        expectedUserId: $this->user->id
    );

    // Retrieve the updated payment split from database (should still be sr_no = 1)
    $updatedPaymentSplit = PaymentTestHelper::getPaymentSplitByCodeAndSerial(
        paymentCode: $updatedPayment->code,
        srNo: 1
    );
    
    // Assert payment split exists and was updated correctly
    expect($updatedPaymentSplit)->not->toBeNull();
    PaymentTestHelper::assertPaymentSplitUpdatedCorrectly(
        paymentSplit: $updatedPaymentSplit,
        payment: $updatedPayment
    );
});


test('payment split validates presence and format of insurer receipt number', function () {
    // ============================================
    // 1. ARRANGE: Create payment splits for testing
    // ============================================
    $payment = \App\Models\Payment::factory()->createForSqlite($this->carQuote);
    
    // Create a payment split with an existing receipt number for case 3
    $existingReceiptNumber = 'INS-REC-EXISTING';
    \App\Models\PaymentSplits::factory()->createForSqlite($payment, [
        'insurer_receipt_number' => $existingReceiptNumber,
    ]);

    // Create another payment split without insurer_receipt_number for testing updates
    \App\Models\PaymentSplits::factory()->createForSqlite($payment, [
        'insurer_receipt_number' => null,
    ]);

    $quoteType = \App\Enums\QuoteTypes::CAR->value;

    // ============================================
    // CASE 1: Test null validation error
    // ============================================
    $response = $this->postJson(
        '/payments/' . $quoteType . '/check-insurer-receipt-number',
        ['insurer_receipt_number' => null]
    );
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['insurer_receipt_number']);

    // ============================================
    // CASE 2: Test with non-existent receipt number (should show "not exists")
    // ============================================
    $nonExistentReceipt = 'INS-REC-NONEXISTENT';
    $response = $this->postJson(
        '/payments/' . $quoteType . '/check-insurer-receipt-number',
        ['insurer_receipt_number' => $nonExistentReceipt]
    );
    $response->assertStatus(200);
    $response->assertJson([
        'status' => true,
        'message' => 'Receipt number does not exist',
    ]);

    // ============================================
    // CASE 3: Test with existing receipt number (should show "exists")
    // ============================================
    $response = $this->postJson(
        '/payments/' . $quoteType . '/check-insurer-receipt-number',
        ['insurer_receipt_number' => $existingReceiptNumber]
    );
    $response->assertStatus(200);
    $response->assertJson([
        'status' => false,
        'message' => 'Receipt number already exists',
    ]);
});