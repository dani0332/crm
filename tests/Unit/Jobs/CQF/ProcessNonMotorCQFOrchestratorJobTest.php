<?php

declare(strict_types=1);

use App\Jobs\CQF\ProcessNonMotorCQFOrchestratorJob;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Log;

test('has correct job configuration', function () {
    $job = new ProcessNonMotorCQFOrchestratorJob;

    expect($job->tries)->toBe(1)
        ->and($job->timeout)->toBe(80);
});

test('implements ShouldBeUnique', function () {
    $job = new ProcessNonMotorCQFOrchestratorJob;

    expect($job)->toBeInstanceOf(ShouldBeUnique::class);
});

test('failed() logs error', function () {
    Log::spy();

    $job = new ProcessNonMotorCQFOrchestratorJob;
    $job->failed(new RuntimeException('test error'));

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message): bool => str_contains($message, 'failed'));
});
