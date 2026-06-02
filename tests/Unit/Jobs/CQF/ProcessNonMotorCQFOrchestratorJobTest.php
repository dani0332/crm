<?php

declare(strict_types=1);

use App\Jobs\CQF\ProcessNonMotorCQFLOBJob;
use App\Jobs\CQF\ProcessNonMotorCQFOrchestratorJob;
use App\Models\RenewalsUploadLeads;
use App\Services\CQF\NonMotor\NonMotorCQFRegistry;
use App\Services\CQF\NonMotor\NonMotorCQFRenewalExecutionService;
use Illuminate\Bus\PendingBatch;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    $this->job = new ProcessNonMotorCQFOrchestratorJob;
});

test('has correct job configuration', function () {
    expect($this->job->tries)->toBe(1)
        ->and($this->job->timeout)->toBe(300);
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

test('dispatches a batch with one LOB job per eligible LOB', function () {
    TestSchemaCreator::createMinimalSchema();
    Bus::fake();

    $mockLead = tap(new RenewalsUploadLeads, fn ($m) => $m->id = 42);

    $service = Mockery::mock(NonMotorCQFRenewalExecutionService::class);
    $service->shouldReceive('getEligibleQuoteCountForLOB')->andReturn(10);
    $service->shouldReceive('createRenewalUploadLeadsForLOB')->andReturn($mockLead);

    $this->job->handle($service);

    $expectedCount = count(NonMotorCQFRegistry::supportedLOBs());

    Bus::assertBatched(function (PendingBatch $batch) use ($expectedCount): bool {
        return $batch->jobs->count() === $expectedCount
            && $batch->jobs->every(fn ($job) => $job instanceof ProcessNonMotorCQFLOBJob);
    });
});

test('skips LOBs with zero eligible quotes', function () {
    TestSchemaCreator::createMinimalSchema();
    Bus::fake();

    $mockLead = tap(new RenewalsUploadLeads, fn ($m) => $m->id = 1);

    $service = Mockery::mock(NonMotorCQFRenewalExecutionService::class);
    // Only the first LOB has eligible quotes; all others return 0.
    $service->shouldReceive('getEligibleQuoteCountForLOB')->andReturn(5, 0, 0, 0, 0, 0);
    $service->shouldReceive('createRenewalUploadLeadsForLOB')->once()->andReturn($mockLead);

    $this->job->handle($service);

    Bus::assertBatched(function (PendingBatch $batch): bool {
        return $batch->jobs->count() === 1;
    });
});

test('does not dispatch a batch when all LOBs have zero eligible quotes', function () {
    TestSchemaCreator::createMinimalSchema();
    Bus::fake();

    $service = Mockery::mock(NonMotorCQFRenewalExecutionService::class);
    $service->shouldReceive('getEligibleQuoteCountForLOB')->andReturn(0);
    $service->shouldReceive('createRenewalUploadLeadsForLOB')->never();

    $this->job->handle($service);

    Bus::assertNothingBatched();
});
