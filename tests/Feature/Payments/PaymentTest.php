<?php

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypes;
use App\Services\OCR\OCRService;
use Database\Factories\ApplicationStorageFactory;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Services\PaymentTestQueryService;
use Tests\Helpers\Payments\PaymentTestAssertionHelper;
use Tests\Helpers\Payments\PaymentTestCreationHelper;
use Tests\Helpers\Payments\PaymentTestDataHelper;
use Tests\Helpers\Payments\PaymentTestPayloadHelper;
use Tests\Helpers\TestDataSeeder;

beforeEach(function () {
    ApplicationStorageFactory::createVatValueForSqlite('5');

    $this->user = TestDataSeeder::createAdminUser();

    // Mock OCRService to avoid dependency resolution issues in HandleInertiaRequests middleware
    $ocrServiceMock = Mockery::mock(OCRService::class);
    $ocrServiceMock->shouldReceive('getEligibleProviders')->andReturn([]);
    $this->app->instance(OCRService::class, $ocrServiceMock);

    $this->actingAs($this->user);
    PaymentTestDataHelper::setupPaymentPermissions($this->user);

    $testData = PaymentTestDataHelper::setupTestData();
    $this->insuranceProvider = $testData['insuranceProvider'];
    $this->carPlan = $testData['carPlan'];
    $this->carQuote = $testData['carQuote'];
    $this->quoteCode = $testData['quoteCode'];
    $this->quoteUuid = $testData['quoteUuid'];

    $this->paymentQueryService = new PaymentTestQueryService;
});

test('payment and payment split can be created using factories', function () {
    $createdEntities = PaymentTestCreationHelper::createPaymentWithSplit($this->carQuote);
    $createdPayment = $createdEntities['payment'];
    $createdPaymentSplit = $createdEntities['paymentSplit'];

    PaymentTestAssertionHelper::assertPaymentCreatedCorrectly(
        payment: $createdPayment,
        carQuote: $this->carQuote,
        expectedPlanId: $this->carPlan->id,
        expectedInsuranceProviderId: $this->insuranceProvider->id,
        expectedUserId: $this->user->id
    );

    PaymentTestAssertionHelper::assertPaymentSplitCreatedCorrectly(
        paymentSplit: $createdPaymentSplit,
        payment: $createdPayment,
        expectedAmount: (float) $this->carQuote->premium
    );

    PaymentTestAssertionHelper::assertObserversRanSuccessfully(
        payment: $createdPayment,
        paymentSplit: $createdPaymentSplit
    );
});

test('payment should be created via endpoint', function () {
    $requestPayload = PaymentTestPayloadHelper::buildPaymentCreationPayload(
        carQuote: $this->carQuote,
        planId: $this->carPlan->id,
        insuranceProviderId: $this->insuranceProvider->id
    );

    $response = $this->post(route('payment-create', ['quoteType' => QuoteTypes::CAR->value]), $requestPayload);

    $response->assertStatus(302);

    $createdPayment = $this->paymentQueryService->getPaymentByQuoteCode($this->carQuote->code);
    expect($createdPayment)->not->toBeNull();
    PaymentTestAssertionHelper::assertPaymentCreatedCorrectly(
        payment: $createdPayment,
        carQuote: $this->carQuote,
        expectedPlanId: $this->carPlan->id,
        expectedInsuranceProviderId: $this->insuranceProvider->id,
        expectedUserId: $this->user->id
    );

    $createdPaymentSplit = $this->paymentQueryService->getPaymentSplitByCodeAndSerial(
        paymentCode: $createdPayment->code,
        srNo: 1
    );

    expect($createdPaymentSplit)->not->toBeNull();
    PaymentTestAssertionHelper::assertPaymentSplitCreatedCorrectly(
        paymentSplit: $createdPaymentSplit,
        payment: $createdPayment,
        expectedAmount: (float) $this->carQuote->premium
    );

    PaymentTestAssertionHelper::assertObserversRanSuccessfully(
        payment: $createdPayment,
        paymentSplit: $createdPaymentSplit
    );
});

test('payment should be updated via endpoint', function () {
    $createdEntities = PaymentTestCreationHelper::createPaymentWithSplit($this->carQuote);
    $existingPayment = $createdEntities['payment'];
    $existingPaymentSplit = $createdEntities['paymentSplit'];

    expect($existingPayment)->not->toBeNull();
    expect($existingPaymentSplit)->not->toBeNull();

    $updatePayload = PaymentTestPayloadHelper::buildPaymentUpdatePayload(
        carQuote: $this->carQuote,
        payment: $existingPayment
    );

    $response = $this->post('/payments/'.QuoteTypes::CAR->value.'/update-new', $updatePayload);

    $response->assertStatus(302);

    $updatedPayment = $this->paymentQueryService->getPaymentByQuoteCode($this->carQuote->code);
    expect($updatedPayment)->not->toBeNull();
    PaymentTestAssertionHelper::assertPaymentUpdatedCorrectly(
        payment: $updatedPayment,
        carQuote: $this->carQuote,
        expectedPlanId: $this->carPlan->id,
        expectedInsuranceProviderId: $this->insuranceProvider->id,
        expectedUserId: $this->user->id
    );

    $updatedPaymentSplit = $this->paymentQueryService->getPaymentSplitByCodeAndSerial(
        paymentCode: $updatedPayment->code,
        srNo: 1
    );

    expect($updatedPaymentSplit)->not->toBeNull();
    PaymentTestAssertionHelper::assertPaymentSplitUpdatedCorrectly(
        paymentSplit: $updatedPaymentSplit,
        payment: $updatedPayment
    );
});

test('payment split validates presence and format of insurer receipt number', function () {
    $payment = PaymentTestCreationHelper::createPayment($this->carQuote);

    $existingReceiptNumber = 'INS-REC-EXISTING';
    PaymentTestCreationHelper::createPaymentSplit($payment, [
        'insurer_receipt_number' => $existingReceiptNumber,
    ]);

    PaymentTestCreationHelper::createPaymentSplit($payment, [
        'insurer_receipt_number' => null,
    ]);

    $quoteType = QuoteTypes::CAR->value;

    $response = $this->postJson(
        '/payments/'.$quoteType.'/check-insurer-receipt-number',
        ['insurer_receipt_number' => null]
    );
    PaymentTestAssertionHelper::assertInsurerReceiptNumberNullValidationError($response);

    $nonExistentReceipt = 'INS-REC-NONEXISTENT';
    $response = $this->postJson(
        '/payments/'.$quoteType.'/check-insurer-receipt-number',
        ['insurer_receipt_number' => $nonExistentReceipt]
    );
    PaymentTestAssertionHelper::assertInsurerReceiptNumberDoesNotExist($response);

    $response = $this->postJson(
        '/payments/'.$quoteType.'/check-insurer-receipt-number',
        ['insurer_receipt_number' => $existingReceiptNumber]
    );
    PaymentTestAssertionHelper::assertInsurerReceiptNumberAlreadyExists($response);
});

test('payment should be approved via endpoint', function () {
    $db = DB::connection('sqlite');
    $db->table('application_storage')->updateOrInsert(
        ['key_name' => ApplicationStorageEnums::SAGE_ENABLED],
        ['value' => '0', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]
    );

    $createdEntities = PaymentTestCreationHelper::createPaymentWithSplitAndCapturedAmount($this->carQuote);
    $existingPayment = $createdEntities['payment'];
    $existingPaymentSplit = $createdEntities['paymentSplit'];

    expect($existingPayment)->not->toBeNull();
    expect($existingPaymentSplit)->not->toBeNull();

    $initialCapturedAmount = (float) ($existingPayment->captured_amount ?? 0);
    $collectionAmount = (float) $existingPaymentSplit->payment_amount;

    $approvePayload = PaymentTestPayloadHelper::buildApprovePaymentPayload(
        carQuote: $this->carQuote,
        paymentSplit: $existingPaymentSplit
    );

    $response = $this->post('/payments/'.QuoteTypes::CAR->value.'/split-payment-approve-decline', $approvePayload);

    $response->assertStatus(302);

    $updatedPayment = $this->paymentQueryService->getPaymentByQuoteCode($this->carQuote->code);
    expect($updatedPayment)->not->toBeNull();
    $updatedPayment->refresh();

    PaymentTestAssertionHelper::assertPaymentCapturedAmountUpdatedCorrectly(
        payment: $updatedPayment,
        expectedCapturedAmount: $initialCapturedAmount + $collectionAmount
    );

    $approvedPaymentSplit = $this->paymentQueryService->getPaymentSplitByCodeAndSerial(
        paymentCode: $updatedPayment->code,
        srNo: 1
    );

    $approvedPaymentSplit->refresh();

    expect($approvedPaymentSplit)->not->toBeNull();
    PaymentTestAssertionHelper::assertPaymentSplitApprovedCorrectly(
        paymentSplit: $approvedPaymentSplit,
        expectedCollectionAmount: $collectionAmount,
        expectedInsurerReceiptNumber: $approvePayload['insurer_receipt_number'],
        expectedUserId: $this->user->id,
        actualAmount: $approvePayload['actual_amount']
    );
});
