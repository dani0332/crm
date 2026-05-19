<?php

declare(strict_types=1);

use App\Enums\InsuranceProviderEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\SageEmbeddedProductEnum;
use App\Enums\SageEnum;
use App\Http\Middleware\BasicAuth;
use App\Jobs\BookEmbeddedProductOnSageJob;
use App\Jobs\ReverseEmbeddedProductOnSageJob;
use App\Models\EmbeddedTransaction;
use App\Models\EpLog;
use App\Models\SageProcess;
use App\Services\SageApiEmbeddedProductService;
use App\Services\SageApiService;
use Illuminate\Http\Response;
use Tests\Helpers\RetargetingEpReminderTestDataHelper;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();
    $this->withoutMiddleware(BasicAuth::class);
});

afterEach(function (): void {
    Mockery::close();
});

describe('POST /api/ep/cancellation-callback', function (): void {
    test('returns 422 when validation fails', function (): void {
        $response = $this->postJson(route('api.ep-cancellation-callback'), []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['etId', 'quoteId', 'quoteTypeId']);
    });

    test('returns 422 when embedded transaction id does not exist', function (): void {
        $response = $this->postJson(route('api.ep-cancellation-callback'), [
            'etId' => 999999,
            'quoteId' => 1,
            'quoteTypeId' => QuoteTypeId::Car,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['etId']);
    });

    test('returns 404 when embedded transaction is not an eligible EP type', function (): void {
        $data = RetargetingEpReminderTestDataHelper::setupRepositoryTestData();
        $data->notAllowedEpTransaction->update([
            'payment_status_id' => PaymentStatusEnum::REFUNDED,
            'sage_status_id' => SageEmbeddedProductEnum::BOOKING_COMPLETED->id(),
        ]);

        $response = $this->postJson(route('api.ep-cancellation-callback'), [
            'etId' => $data->notAllowedEpTransaction->id,
            'quoteId' => $data->carQuotePolicyBooked->id,
            'quoteTypeId' => QuoteTypeId::Car,
        ]);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    });

    test('returns 422 when payment is not refunded', function (): void {
        $data = RetargetingEpReminderTestDataHelper::setupTestData();

        $response = $this->postJson(route('api.ep-cancellation-callback'), [
            'etId' => $data['epMDXTransaction']->id,
            'quoteId' => $data['carQuote']->id,
            'quoteTypeId' => QuoteTypeId::Car,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('message', 'Embedded transaction payment is not in refunded status');
    });

    test('returns 200 when sage is not booked and skips scheduling', function (): void {
        $data = RetargetingEpReminderTestDataHelper::setupTestData();
        $data['epMDXTransaction']->update([
            'payment_status_id' => PaymentStatusEnum::REFUNDED,
            'sage_status_id' => null,
        ]);

        $response = $this->postJson(route('api.ep-cancellation-callback'), [
            'etId' => $data['epMDXTransaction']->id,
            'quoteId' => $data['carQuote']->id,
            'quoteTypeId' => QuoteTypeId::Car,
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', true);
        expect(EpLog::query()->where('embedded_transaction_id', $data['epMDXTransaction']->id)->where('event', 'sage_reversal_callback_skipped')->exists())->toBeTrue();
    });

    test('does not treat queued sage booking as reversal skipped on cancellation callback', function (): void {
        $data = RetargetingEpReminderTestDataHelper::setupTestData();
        $data['insuranceProvider']->update(['code' => InsuranceProviderEnum::OIC->value]);
        $data['epMDXTransaction']->update([
            'payment_status_id' => PaymentStatusEnum::REFUNDED,
            'sage_status_id' => SageEmbeddedProductEnum::BOOKING_QUEUED->id(),
        ]);

        $this->mock(SageApiEmbeddedProductService::class, function ($mock) use ($data): void {
            $mock->shouldReceive('scheduleReversalOfEmbeddedProduct')
                ->once()
                ->andReturn([
                    'status' => true,
                    'message' => 'scheduled',
                    'embedded_transaction_id' => $data['epMDXTransaction']->id,
                ]);
        });

        $response = $this->postJson(route('api.ep-cancellation-callback'), [
            'etId' => $data['epMDXTransaction']->id,
            'quoteId' => $data['carQuote']->id,
            'quoteTypeId' => QuoteTypeId::Car,
        ]);

        $response->assertOk();
        expect(
            EpLog::query()
                ->where('embedded_transaction_id', $data['epMDXTransaction']->id)
                ->where('event', 'sage_reversal_callback_skipped')
                ->exists()
        )->toBeFalse();
    });

    test('returns 200 and schedules reversal when refunded and booked', function (): void {
        $data = RetargetingEpReminderTestDataHelper::setupTestData();
        $data['insuranceProvider']->update(['code' => InsuranceProviderEnum::OIC->value]);
        $data['epMDXTransaction']->update([
            'payment_status_id' => PaymentStatusEnum::REFUNDED,
            'sage_status_id' => SageEmbeddedProductEnum::BOOKING_COMPLETED->id(),
        ]);

        $this->mock(SageApiEmbeddedProductService::class, function ($mock) use ($data): void {
            $mock->shouldReceive('scheduleReversalOfEmbeddedProduct')
                ->once()
                ->andReturn([
                    'status' => true,
                    'message' => 'scheduled',
                    'embedded_transaction_id' => $data['epMDXTransaction']->id,
                ]);
        });

        $response = $this->postJson(route('api.ep-cancellation-callback'), [
            'etId' => $data['epMDXTransaction']->id,
            'quoteId' => $data['carQuote']->id,
            'quoteTypeId' => QuoteTypeId::Car,
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', true);
        expect(EpLog::query()->where('embedded_transaction_id', $data['epMDXTransaction']->id)->where('event', 'sage_reversal_scheduled')->exists())->toBeTrue();
    });
});

describe('BookEmbeddedProductOnSageJob', function (): void {
    test('aborts sage booking when embedded transaction payment is refunded', function (): void {
        $data = RetargetingEpReminderTestDataHelper::setupTestData();
        $data['insuranceProvider']->update(['code' => InsuranceProviderEnum::OIC->value]);
        $data['epMDXTransaction']->update([
            'payment_status_id' => PaymentStatusEnum::REFUNDED,
            'sage_status_id' => SageEmbeddedProductEnum::BOOKING_QUEUED->id(),
        ]);

        $sageRequest = (object) [
            'insurerID' => $data['insuranceProvider']->id,
            'epShortCode' => 'MDX',
            'sageProcessRequestType' => SageEnum::SAGE_PROCESS_BOOK_EMBEDDED_PRODUCT_REQUEST,
        ];
        $request = (object) [
            'modelType' => 'Car',
            'quoteId' => $data['carQuote']->id,
        ];

        $sageProcess = SageProcess::query()->create([
            'user_id' => null,
            'insurance_provider_id' => $data['insuranceProvider']->id,
            'model_type' => EmbeddedTransaction::class,
            'model_id' => $data['epMDXTransaction']->id,
            'request' => json_encode(['sagePayload' => $sageRequest, 'requestPayload' => $request]),
            'status' => SageEnum::SAGE_PROCESS_PENDING_STATUS,
        ]);

        BookEmbeddedProductOnSageJob::dispatchSync($sageRequest, $data['epMDXTransaction'], $request, $sageProcess);

        $data['epMDXTransaction']->refresh();
        $sageProcess->refresh();
        expect($data['epMDXTransaction']->sage_status_id)->toBe(SageEmbeddedProductEnum::BOOKING_CANCELLED->id());
        expect($sageProcess->status)->toBe(SageEnum::SAGE_PROCESS_FAILED_STATUS);
    });
});

describe('ReverseEmbeddedProductOnSageJob', function (): void {
    test('completes without sage api when booking was queued but never posted to sage', function (): void {
        $data = RetargetingEpReminderTestDataHelper::setupTestData();
        $data['insuranceProvider']->update(['code' => InsuranceProviderEnum::OIC->value]);
        $data['epMDXTransaction']->update([
            'payment_status_id' => PaymentStatusEnum::REFUNDED,
            'sage_status_id' => SageEmbeddedProductEnum::BOOKING_QUEUED->id(),
        ]);

        $this->mock(SageApiService::class, function ($mock): void {
            $mock->shouldReceive('updateSageProcessStatus')->zeroOrMoreTimes();
            $mock->shouldReceive('scheduleSageProcesses')->zeroOrMoreTimes();
        });

        $sageRequest = (object) [
            'insurerID' => $data['insuranceProvider']->id,
            'epShortCode' => 'MDX',
        ];
        $request = (object) [
            'modelType' => 'Car',
            'quoteId' => $data['carQuote']->id,
            'quoteTypeId' => QuoteTypeId::Car,
        ];

        $sageProcess = SageProcess::query()->create([
            'user_id' => null,
            'insurance_provider_id' => $data['insuranceProvider']->id,
            'model_type' => EmbeddedTransaction::class,
            'model_id' => $data['epMDXTransaction']->id,
            'request' => json_encode(['sagePayload' => $sageRequest, 'requestPayload' => $request]),
            'status' => SageEnum::SAGE_PROCESS_PENDING_STATUS,
        ]);

        ReverseEmbeddedProductOnSageJob::dispatchSync($sageRequest, $data['epMDXTransaction'], $request, $sageProcess);

        $data['epMDXTransaction']->refresh();
        expect($data['epMDXTransaction']->sage_status_id)->toBe(SageEmbeddedProductEnum::BOOKING_CANCELLED->id());
        expect(
            EpLog::query()
                ->where('embedded_transaction_id', $data['epMDXTransaction']->id)
                ->where('event', 'sage_reversal_not_required')
                ->exists()
        )->toBeTrue();
    });

    test('writes success ep log when sage reversal succeeds', function (): void {
        $data = RetargetingEpReminderTestDataHelper::setupTestData();
        $data['insuranceProvider']->update(['code' => InsuranceProviderEnum::OIC->value]);

        $this->mock(SageApiEmbeddedProductService::class, function ($mock): void {
            $mock->shouldReceive('bookReversalOfEmbeddedProductOnSageAfterImcrmRefund')->once()->andReturn(['status' => true, 'message' => 'ok']);
            $mock->shouldReceive('updateAndLogEPBookingStatus')->zeroOrMoreTimes();
        });
        $this->mock(SageApiService::class, function ($mock): void {
            $mock->shouldReceive('updateSageProcessStatus')->zeroOrMoreTimes();
            $mock->shouldReceive('scheduleSageProcesses')->zeroOrMoreTimes();
        });

        $sageRequest = (object) [
            'insurerID' => $data['insuranceProvider']->id,
            'epShortCode' => 'MDX',
        ];
        $request = (object) [
            'modelType' => 'Car',
            'quoteId' => $data['carQuote']->id,
            'quoteTypeId' => QuoteTypeId::Car,
        ];

        $sageProcess = SageProcess::query()->create([
            'user_id' => null,
            'insurance_provider_id' => $data['insuranceProvider']->id,
            'model_type' => EmbeddedTransaction::class,
            'model_id' => $data['epMDXTransaction']->id,
            'request' => json_encode(['sagePayload' => $sageRequest, 'requestPayload' => $request]),
            'status' => SageEnum::SAGE_PROCESS_PENDING_STATUS,
        ]);

        ReverseEmbeddedProductOnSageJob::dispatchSync($sageRequest, $data['epMDXTransaction'], $request, $sageProcess);

        expect(
            EpLog::query()
                ->where('embedded_transaction_id', $data['epMDXTransaction']->id)
                ->where('event', 'sage_reversal_success')
                ->exists()
        )->toBeTrue();
    });

    test('does not schedule sage processes when reversal fails before final attempt', function (): void {
        config(['constants.EP_SAGE_REVERSAL_MAX_TRIES' => 3]);

        $data = RetargetingEpReminderTestDataHelper::setupTestData();
        $data['insuranceProvider']->update(['code' => InsuranceProviderEnum::OIC->value]);

        $this->mock(SageApiEmbeddedProductService::class, function ($mock): void {
            $mock->shouldReceive('bookReversalOfEmbeddedProductOnSageAfterImcrmRefund')
                ->once()
                ->withArgs(fn (array $args, ?string $epShortCode, bool $updateStatusOnFailure): bool => $updateStatusOnFailure === false)
                ->andReturn(['status' => false, 'message' => 'transient sage failure']);
            $mock->shouldReceive('updateAndLogEPBookingStatus')->zeroOrMoreTimes();
        });
        $this->mock(SageApiService::class, function ($mock): void {
            $mock->shouldReceive('updateSageProcessStatus')->zeroOrMoreTimes();
            $mock->shouldReceive('scheduleSageProcesses')->never();
        });

        $sageRequest = (object) [
            'insurerID' => $data['insuranceProvider']->id,
            'epShortCode' => 'MDX',
        ];
        $request = (object) [
            'modelType' => 'Car',
            'quoteId' => $data['carQuote']->id,
            'quoteTypeId' => QuoteTypeId::Car,
        ];

        $sageProcess = SageProcess::query()->create([
            'user_id' => null,
            'insurance_provider_id' => $data['insuranceProvider']->id,
            'model_type' => EmbeddedTransaction::class,
            'model_id' => $data['epMDXTransaction']->id,
            'request' => json_encode(['sagePayload' => $sageRequest, 'requestPayload' => $request]),
            'status' => SageEnum::SAGE_PROCESS_PENDING_STATUS,
        ]);

        $job = new ReverseEmbeddedProductOnSageJob($sageRequest, $data['epMDXTransaction'], $request, $sageProcess);

        expect(fn () => $job->handle())->toThrow(RuntimeException::class, 'transient sage failure');

        expect(
            EpLog::query()
                ->where('embedded_transaction_id', $data['epMDXTransaction']->id)
                ->where('event', 'sage_reversal_attempt')
                ->exists()
        )->toBeTrue();
    });
});
