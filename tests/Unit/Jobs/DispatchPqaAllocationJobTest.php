<?php

declare(strict_types=1);

use App\Enums\QuoteTypes;
use App\Jobs\DispatchPqaAllocationJob;
use App\Services\PqaAllocation\PqaAllocationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Log;

test('job implements ShouldBeUnique', function () {
    $job = new DispatchPqaAllocationJob('test-uuid', QuoteTypes::HEALTH);

    expect($job)->toBeInstanceOf(ShouldBeUnique::class);
});

test('uniqueFor is 300 seconds', function () {
    $job = new DispatchPqaAllocationJob('test-uuid', QuoteTypes::HEALTH);

    expect($job->uniqueFor)->toBe(300);
});

test('uniqueId combines quote type and uuid', function () {
    $job = new DispatchPqaAllocationJob('abc-123', QuoteTypes::HEALTH);

    expect($job->uniqueId())->toBe('Health:abc-123');
});

test('uniqueId differs across quote types for the same uuid', function () {
    $uuid = 'shared-uuid';

    $healthJob = new DispatchPqaAllocationJob($uuid, QuoteTypes::HEALTH);
    $corplineJob = new DispatchPqaAllocationJob($uuid, QuoteTypes::CORPLINE);

    expect($healthJob->uniqueId())->not->toBe($corplineJob->uniqueId());
});

test('handle calls PqaAllocationService::executeAllocation with correct arguments', function () {
    $uuid = 'quote-uuid-xyz';
    $quoteType = QuoteTypes::HEALTH;

    $mock = Mockery::mock(PqaAllocationService::class);
    $mock->shouldReceive('executeAllocation')->once()->with($uuid, false, $quoteType);
    app()->instance(PqaAllocationService::class, $mock);

    $job = new DispatchPqaAllocationJob($uuid, $quoteType);
    $job->handle();
});

test('handle passes the correct quote type to allocation service', function () {
    $uuid = 'corpline-uuid';
    $quoteType = QuoteTypes::CORPLINE;

    $mock = Mockery::mock(PqaAllocationService::class);
    $mock->shouldReceive('executeAllocation')->once()->with($uuid, false, $quoteType);
    app()->instance(PqaAllocationService::class, $mock);

    $job = new DispatchPqaAllocationJob($uuid, $quoteType);
    $job->handle();
});

test('failed logs an error with quote uuid and type', function () {
    Log::spy();

    $job = new DispatchPqaAllocationJob('failing-uuid', QuoteTypes::HEALTH);
    $job->failed(new RuntimeException('Allocation failed'));

    Log::shouldHaveReceived('error')->once();
});

test('failed does not throw for any throwable', function () {
    Log::spy();

    $job = new DispatchPqaAllocationJob('some-uuid', QuoteTypes::HEALTH);

    expect(fn () => $job->failed(new Exception('boom')))->not->toThrow(Throwable::class);
});
