<?php

declare(strict_types=1);

use App\Enums\QuoteTypes;
use App\Jobs\CQF\ProcessNonMotorCQFLOBJob;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Log;

test('has correct job configuration', function () {
    $job = new ProcessNonMotorCQFLOBJob(
        renewalsUploadLeadsId: 10,
        quoteType: QuoteTypes::BIKE,
        startDate: '2026-05-20',
        renewalDaysThreshold: 30,
    );

    expect($job->tries)->toBe(1)
        ->and($job->timeout)->toBe(80)
        ->and($job->uniqueFor)->toBe(300)
        ->and($job->queue)->toBe('default'); // set via onQueue('default') in constructor
});

test('implements ShouldBeUnique with correct uniqueId', function () {
    $job = new ProcessNonMotorCQFLOBJob(
        renewalsUploadLeadsId: 42,
        quoteType: QuoteTypes::BIKE,
        startDate: '2026-05-20',
        renewalDaysThreshold: 30,
    );

    expect($job)->toBeInstanceOf(ShouldBeUnique::class)
        ->and($job->uniqueId())->toBe('42-Bike');
});

test('failed() logs error with expected context', function () {
    Log::spy();

    $job = new ProcessNonMotorCQFLOBJob(
        renewalsUploadLeadsId: 99,
        quoteType: QuoteTypes::PET,
        startDate: '2026-05-20',
        renewalDaysThreshold: 30,
    );

    $job->failed(new RuntimeException('test error'));

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message): bool => str_contains($message, 'Job failed'));
});
