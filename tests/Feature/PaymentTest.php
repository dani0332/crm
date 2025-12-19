<?php

use Tests\Helpers\PaymentTestHelper;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

// Will run for each test 
beforeEach(function () {
    // Initialize test database schema
    TestSchemaCreator::createMinimalSchema();
    
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
    // ARRANGE: Prepare test data
    // ============================================
    // No additional arrangement needed - data is prepared in beforeEach
    
    // ============================================
    // ACT: Create payment and payment split using factories
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
    // ASSERT: Verify the results
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