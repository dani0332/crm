<?php

declare(strict_types=1);

use App\Jobs\CQF\ProcessNonMotorCQFOrchestratorJob;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Log;

$job = new ProcessNonMotorCQFOrchestratorJob;

test('has correct job configuration', function () use ($job) {
    expect($job->tries)->toBe(1)
        ->and($job->timeout)->toBe(60);
});

test('implements ShouldBeUnique', function () use ($job) {
    expect($job)->toBeInstanceOf(ShouldBeUnique::class);
});

test('failed() logs error', function () use ($job) {
    Log::spy();

    $job->failed(new RuntimeException('test error'));

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message): bool => str_contains($message, 'failed'));
});
