<?php

declare(strict_types=1);

use App\Enums\QuoteStatusEnum;
use App\Jobs\EP\RetargetEpReminderJob;
use Illuminate\Support\Facades\Bus;
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

        test('dispatches RetargetEpReminderJob when status becomes PolicyBooked', function () {
            Bus::fake();

            $this->carQuote->update(['quote_status_id' => QuoteStatusEnum::PolicyBooked]);

            Bus::assertDispatchedOnce(RetargetEpReminderJob::class);
        });
    });
});
