<?php

declare(strict_types=1);

use App\Enums\SageEmbeddedProductEnum;
use App\Enums\SageEnum;
use App\Models\EmbeddedTransaction;
use App\Models\InsurerRequestResponse;
use App\Models\SageApiLog;
use App\Services\SageApiEmbeddedProductService;
use App\Services\SageApiService;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\RetargetingEpReminderTestDataHelper;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();

    if (! Schema::hasColumn('sage_api_logs', 'sage_payload')) {
        Schema::table('sage_api_logs', function ($table): void {
            $table->text('sage_payload')->nullable();
        });
    }
});

afterEach(function (): void {
    Mockery::close();
});

/**
 * @return array{quote: object, ep: EmbeddedTransaction, sageRequest: object, insurerResponse: InsurerRequestResponse}
 */
function imcrmReversalTestContext(): array
{
    $data = RetargetingEpReminderTestDataHelper::setupTestData();
    $ep = $data['epMDXTransaction'];
    $ep->update(['sage_status_id' => SageEmbeddedProductEnum::BOOKING_COMPLETED->id()]);

    SageApiLog::query()->create([
        'section_id' => $ep->id,
        'section_type' => $ep->getMorphClass(),
        'sage_request_type' => SageEnum::EP_SRT_CREATE_AR_PREM_COMM_INV,
        'sage_payload' => json_encode(['Invoices' => [['CustomerNumber' => 'CUST-001']]]),
        'status' => SageEnum::STATUS_SUCCESS,
    ]);

    SageApiLog::query()->create([
        'section_id' => $ep->id,
        'section_type' => $ep->getMorphClass(),
        'step' => 25,
        'response' => json_encode(['BatchNumber' => 'BATCH-001']),
        'status' => SageEnum::STATUS_SUCCESS,
    ]);
    SageApiLog::query()->create([
        'section_id' => $ep->id,
        'section_type' => $ep->getMorphClass(),
        'step' => 26,
        'response' => json_encode(''),
        'status' => SageEnum::STATUS_SUCCESS,
    ]);

    $payment = new stdClass;
    $payment->paymentSplits = collect();
    $ep->setRelation('payment', $payment);

    $insurerResponse = new InsurerRequestResponse;
    $insurerResponse->setRelation('insuranceProvider', $data['insuranceProvider']);
    $insurerResponse->response = json_encode([
        'policy_number' => 'POL-001',
        'additional_data' => (object) [
            'tax_invoice_document_number' => 'INV-001',
            'tax_invoice_buyer_document_number' => 'COMM-001',
            'broker_commission_amount' => 10,
            'broker_commission_vat_amount' => 1,
            'broker_commission_total_amount' => 11,
        ],
        'payments' => [(object) ['amount' => 100]],
        'pricing' => (object) [
            'tax_amount' => 5,
            'policy_price' => 95,
            'total_price' => 100,
        ],
        'start_date' => '01/01/2025',
        'end_date' => '01/01/2026',
    ]);

    return [
        'quote' => $data['carQuote'],
        'ep' => $ep,
        'sageRequest' => (object) [
            'userId' => 1,
            'bookingDate' => '2025-01-01',
            'customerId' => 'CUST-001',
            'insured' => 'Test Insured',
            'mainClassInsurance' => 'MOTOR',
            'manager' => 'Manager',
            'policyHolder' => 'Holder',
            'advisorName' => 'Advisor',
            'premiumCollectedBy' => 'Broker',
        ],
        'insurerResponse' => $insurerResponse,
    ];
}

function injectSageApiService(SageApiEmbeddedProductService $service, SageApiService $sageApiService): void
{
    $property = new ReflectionProperty(SageApiEmbeddedProductService::class, 'sageApiService');
    $property->setAccessible(true);
    $property->setValue($service, $sageApiService);
}

test('does not set BOOKING_REVERSAL_FAILED on Sage conflict when terminal failure update is requested', function (): void {
    $context = imcrmReversalTestContext();

    $sageApiService = Mockery::mock(SageApiService::class)->makePartial();
    $sageApiService->shouldReceive('postToSage300')
        ->andReturn(json_encode(['error' => ['message' => ['value' => 'processing conflict']]]));
    $sageApiService->shouldReceive('logSageApiCall')->zeroOrMoreTimes();

    $service = Mockery::mock(SageApiEmbeddedProductService::class)->makePartial();
    $service->shouldReceive('getInsurerRequestResponse')
        ->once()
        ->andReturn($context['insurerResponse']);
    $service->shouldReceive('updateAndLogEPBookingStatus')->never();
    injectSageApiService($service, $sageApiService);

    $result = $service->bookReversalOfEmbeddedProductOnSageAfterImcrmRefund(
        [$context['quote'], $context['ep'], $context['sageRequest'], $context['ep']],
        'MDX',
        true,
    );

    expect($result)->toMatchArray([
        'status' => false,
        'message' => SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE,
    ]);
});

test('sets BOOKING_REVERSAL_FAILED when terminal failure update is requested for a non-conflict failure', function (): void {
    $data = RetargetingEpReminderTestDataHelper::setupTestData();
    $ep = $data['epMDXTransaction'];
    $ep->update(['sage_status_id' => SageEmbeddedProductEnum::BOOKING_COMPLETED->id()]);

    $service = Mockery::mock(SageApiEmbeddedProductService::class)->makePartial();
    $service->shouldReceive('updateAndLogEPBookingStatus')
        ->once()
        ->with($ep, SageEmbeddedProductEnum::BOOKING_REVERSAL_FAILED->id(), Mockery::type('string'));

    $result = $service->bookReversalOfEmbeddedProductOnSageAfterImcrmRefund(
        [$data['carQuote'], $ep, (object) ['userId' => 1], $ep],
        'MDX',
        true,
    );

    expect($result['status'])->toBeFalse();
    expect($result['message'])->toContain('No Sage AR premium booking log found');
});

test('does not set BOOKING_REVERSAL_FAILED on non-terminal failure when update flag is false', function (): void {
    $data = RetargetingEpReminderTestDataHelper::setupTestData();
    $ep = $data['epMDXTransaction'];
    $ep->update(['sage_status_id' => SageEmbeddedProductEnum::BOOKING_COMPLETED->id()]);

    $service = Mockery::mock(SageApiEmbeddedProductService::class)->makePartial();
    $service->shouldReceive('updateAndLogEPBookingStatus')->never();

    $service->bookReversalOfEmbeddedProductOnSageAfterImcrmRefund(
        [$data['carQuote'], $ep, (object) ['userId' => 1], $ep],
        'MDX',
        false,
    );
});
