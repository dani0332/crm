<?php

declare(strict_types=1);

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Services\EmbeddedTransactionService;
use Illuminate\Http\Response;
use Tests\Helpers\RetargetingEpReminderTestDataHelper;
use Tests\Helpers\TestSchemaCreator;

$observerGroupData = null;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

afterEach(function () {
    Mockery::close();
});

describe('CarQuoteObserver', function () use (&$observerGroupData) {
    describe('PolicyBooked – retarget EP reminder', function () use (&$observerGroupData) {
        beforeEach(function () use (&$observerGroupData) {
            if ($observerGroupData === null) {
                $observerGroupData = RetargetingEpReminderTestDataHelper::setupTestData();
            }
            $this->carQuote = $observerGroupData['carQuote'];
            $this->quoteId = $observerGroupData['quoteId'];
            $this->quoteUuid = $observerGroupData['quoteUuid'];
            $this->quoteCode = $observerGroupData['quoteCode'];
            $this->epMDXTransaction = $observerGroupData['epMDXTransaction'];
        });

        test('retargetEpReminder is called with the lead and QuoteTypeId Car', function () {
            $this->mock(EmbeddedTransactionService::class, function ($mock) {
                $mock->shouldReceive('isRetargetingEpReminderEnabled')
                    ->once()
                    ->andReturn(true);
                $mock->shouldReceive('retargetEpReminder')
                    ->once()
                    ->with(
                        Mockery::type(CarQuote::class),
                        QuoteTypeId::Car
                    )
                    ->andReturn([
                        [
                            'embeddedTransactionCode' => $this->epMDXTransaction->code,
                            'status_code' => Response::HTTP_OK,
                            'message' => '',
                        ],
                    ]);
            });

            $this->carQuote->update(['quote_status_id' => QuoteStatusEnum::PolicyBooked]);

            expect(true)->toBeTrue();
        });

        test('when retargetEpReminder throws an exception, exception is caught and does not bubble up', function () {
            $this->mock(EmbeddedTransactionService::class, function ($mock) {
                $mock->shouldReceive('isRetargetingEpReminderEnabled')
                    ->once()
                    ->andReturn(true);
                $mock->shouldReceive('retargetEpReminder')
                    ->once()
                    ->with(
                        Mockery::type(CarQuote::class),
                        QuoteTypeId::Car
                    )
                    ->andThrow(new \Exception('Retarget EP reminder service error'));
            });

            $this->carQuote->update(['quote_status_id' => QuoteStatusEnum::PolicyBooked]);

            // No assertion needed: if the observer did not catch the exception, the test would fail with an uncaught exception.
        });
    });
});
