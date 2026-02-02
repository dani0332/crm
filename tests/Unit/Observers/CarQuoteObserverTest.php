<?php

declare(strict_types=1);

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Services\EmbeddedTransactionService;
use Tests\Helpers\Payments\PaymentTestDataHelper;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    $testData = PaymentTestDataHelper::setupTestData();
    $this->carQuote = $testData['carQuote'];
});

afterEach(function () {
    Mockery::close();
});

describe('CarQuoteObserver', function () {
    describe('PolicyBooked – retarget EP reminder', function () {
        test('retargetEpReminder is called with the lead and QuoteTypeId Car', function () {
            $this->mock(EmbeddedTransactionService::class, function ($mock) {
                $mock->shouldReceive('retargetEpReminder')
                    ->once()
                    ->with(
                        Mockery::type(CarQuote::class),
                        QuoteTypeId::Car
                    )
                    ->andReturn(true);
            });

            $this->carQuote->update(['quote_status_id' => QuoteStatusEnum::PolicyBooked]);

            expect(true)->toBeTrue();
        });

        test('when retargetEpReminder throws an exception, exception is caught and does not bubble up', function () {
            $this->mock(EmbeddedTransactionService::class, function ($mock) {
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
