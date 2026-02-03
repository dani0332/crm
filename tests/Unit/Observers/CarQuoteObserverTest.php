<?php

declare(strict_types=1);

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Services\EmbeddedTransactionService;
use Illuminate\Http\Response;
use Tests\Helpers\Payments\PaymentTestDataHelper;
use Tests\Helpers\RetargetingEpReminderTestDataHelper;
use Tests\Helpers\TestSchemaCreator;

// return false when retargeting is not enabled
// repo query test
beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    $testData = RetargetingEpReminderTestDataHelper::setupTestData();
    $this->carQuote = $testData['carQuote'];
    $this->quoteId = $testData['quoteId'];
    $this->quoteUuid = $testData['quoteUuid'];
    $this->quoteCode = $testData['quoteCode'];
    $this->epMDXTransaction = $testData['epMDXTransaction'];
});

afterEach(function () {
    Mockery::close();
});

describe('CarQuoteObserver', function () {
    describe('PolicyBooked – retarget EP reminder', function () {
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
                        ]
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
