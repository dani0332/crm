<?php

declare(strict_types=1);

use App\Jobs\CQF\ProcessNonMotorCQFOrchestratorJob;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->job = new ProcessNonMotorCQFOrchestratorJob;
});

test('has correct job configuration', function () {
    expect($this->job->tries)->toBe(1)
        ->and($this->job->timeout)->toBe(60);
});

test('implements ShouldBeUnique', function () {
    expect($this->job)->toBeInstanceOf(ShouldBeUnique::class);
});

test('failed() logs error', function () {
    Log::spy();

    $this->job->failed(new RuntimeException('test error'));

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message): bool => str_contains($message, 'failed'));
});
