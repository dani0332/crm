<?php

declare(strict_types=1);

use App\Enums\QuoteTypes;
use App\Jobs\CQF\ProcessNonMotorCQFQuoteJob;
use App\Services\CQF\NonMotor\NonMotorCQFRenewalExecutionService;

test('has correct job configuration', function () {
    $job = new ProcessNonMotorCQFQuoteJob(
        quoteId: 1,
        source: QuoteTypes::PERSONAL->value,
        quoteType: QuoteTypes::BIKE,
        renewalsUploadLeadsId: 10,
        renewalDaysThreshold: 30,
    );

    expect($job->tries)->toBe(3)
        ->and($job->timeout)->toBe(80)
        ->and($job->backoff())->toBe([10, 30, 60])
        ->and($job->queue)->toBe('default');
});

test('delegates to execution service with correct arguments', function () {
    $executionService = Mockery::mock(NonMotorCQFRenewalExecutionService::class);
    $executionService->shouldReceive('processQuoteForJob')
        ->once()
        ->with(42, QuoteTypes::PERSONAL->value, QuoteTypes::PET, 7, 60);

    $job = new ProcessNonMotorCQFQuoteJob(
        quoteId: 42,
        source: QuoteTypes::PERSONAL->value,
        quoteType: QuoteTypes::PET,
        renewalsUploadLeadsId: 7,
        renewalDaysThreshold: 60,
    );

    $job->handle($executionService);
});

afterEach(function () {
    Mockery::close();
});
