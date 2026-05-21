<?php

declare(strict_types=1);

use App\Enums\InsuranceProviderEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\SageEmbeddedProductEnum;
use App\Enums\SageEnum;
use App\Factories\SagePayloadFactory;
use App\Models\EmbeddedTransaction;
use App\Models\SageProcess;
use App\Services\SageApiEmbeddedProductService;
use App\Services\SageApiService;
use Tests\Helpers\Payments\PaymentTestCreationHelper;
use Tests\Helpers\RetargetingEpReminderTestDataHelper;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();
});

afterEach(function (): void {
    Mockery::close();
});

function injectSageApiServiceForScheduleReversalTest(SageApiEmbeddedProductService $service, SageApiService $sageApiService): void
{
    $property = new ReflectionProperty(SageApiEmbeddedProductService::class, 'sageApiService');
    $property->setAccessible(true);
    $property->setValue($service, $sageApiService);
}

test('scheduleReversalOfEmbeddedProduct does not overwrite sage process while book request is active', function (): void {
    $data = RetargetingEpReminderTestDataHelper::setupTestData();
    $data['insuranceProvider']->update(['code' => InsuranceProviderEnum::OIC->value]);
    $data['epMDXTransaction']->update([
        'payment_status_id' => PaymentStatusEnum::REFUNDED,
        'sage_status_id' => SageEmbeddedProductEnum::BOOKING_QUEUED->id(),
    ]);

    PaymentTestCreationHelper::createPaymentWithSplit($data['carQuote']);

    $bookSageRequest = (object) [
        'sageProcessRequestType' => SageEnum::SAGE_PROCESS_BOOK_EMBEDDED_PRODUCT_REQUEST,
    ];
    $originalRequest = json_encode([
        'sagePayload' => $bookSageRequest,
        'requestPayload' => ['modelType' => 'Car', 'quoteId' => $data['carQuote']->id],
    ]);

    $sageProcess = SageProcess::factory()->create([
        'insurance_provider_id' => $data['insuranceProvider']->id,
        'model_type' => EmbeddedTransaction::class,
        'model_id' => $data['epMDXTransaction']->id,
        'request' => $originalRequest,
        'status' => SageEnum::SAGE_PROCESS_PROCESSING_STATUS,
    ]);

    $sageApiService = Mockery::mock(SageApiService::class)->makePartial();
    $sageApiService->shouldReceive('verifySageCustomer')->andReturn('CUST-001');
    $sageApiService->shouldReceive('scheduleSageProcesses')->never();

    $this->mock(SagePayloadFactory::class, function ($mock): void {
        $mock->shouldReceive('sagePayLoad')->andReturn((object) ['userId' => 1]);
    });

    $service = new SageApiEmbeddedProductService;
    injectSageApiServiceForScheduleReversalTest($service, $sageApiService);

    $result = $service->scheduleReversalOfEmbeddedProduct([
        'etId' => $data['epMDXTransaction']->id,
        'quoteId' => $data['carQuote']->id,
        'quoteTypeId' => QuoteTypeId::Car,
    ]);

    expect($result)->toMatchArray([
        'status' => false,
    ]);
    expect($result['message'])->toContain('booking is still pending or processing');

    $sageProcess->refresh();
    expect($sageProcess->request)->toBe($originalRequest);
    expect($sageProcess->status)->toBe(SageEnum::SAGE_PROCESS_PROCESSING_STATUS);
});

test('scheduleReversalOfEmbeddedProduct can replace completed book sage process with reversal', function (): void {
    $data = RetargetingEpReminderTestDataHelper::setupTestData();
    $data['insuranceProvider']->update(['code' => InsuranceProviderEnum::OIC->value]);
    $data['epMDXTransaction']->update([
        'payment_status_id' => PaymentStatusEnum::REFUNDED,
        'sage_status_id' => SageEmbeddedProductEnum::BOOKING_COMPLETED->id(),
    ]);

    PaymentTestCreationHelper::createPaymentWithSplit($data['carQuote']);

    $bookSageRequest = (object) [
        'sageProcessRequestType' => SageEnum::SAGE_PROCESS_BOOK_EMBEDDED_PRODUCT_REQUEST,
    ];

    $sageProcess = SageProcess::factory()->create([
        'insurance_provider_id' => $data['insuranceProvider']->id,
        'model_type' => EmbeddedTransaction::class,
        'model_id' => $data['epMDXTransaction']->id,
        'request' => json_encode([
            'sagePayload' => $bookSageRequest,
            'requestPayload' => ['modelType' => 'Car', 'quoteId' => $data['carQuote']->id],
        ]),
        'status' => SageEnum::SAGE_PROCESS_COMPLETED_STATUS,
    ]);

    $sageApiService = Mockery::mock(SageApiService::class)->makePartial();
    $sageApiService->shouldReceive('verifySageCustomer')->andReturn('CUST-001');
    $sageApiService->shouldReceive('scheduleSageProcesses')->once();

    $this->mock(SagePayloadFactory::class, function ($mock): void {
        $mock->shouldReceive('sagePayLoad')->andReturn((object) [
            'userId' => 1,
            'insurerID' => 1,
        ]);
    });

    $service = Mockery::mock(SageApiEmbeddedProductService::class)->makePartial();
    $service->shouldReceive('updateAndLogEPBookingStatus')->once();
    injectSageApiServiceForScheduleReversalTest($service, $sageApiService);

    $result = $service->scheduleReversalOfEmbeddedProduct([
        'etId' => $data['epMDXTransaction']->id,
        'quoteId' => $data['carQuote']->id,
        'quoteTypeId' => QuoteTypeId::Car,
    ]);

    expect($result['status'])->toBeTrue();

    $sageProcess->refresh();
    $decoded = json_decode($sageProcess->request ?? '', true);
    expect($decoded['sagePayload']['sageProcessRequestType'] ?? null)
        ->toBe(SageEnum::SAGE_PROCESS_REVERSE_EMBEDDED_PRODUCT_REQUEST);
    expect($sageProcess->status)->toBe(SageEnum::SAGE_PROCESS_PENDING_STATUS);
});
