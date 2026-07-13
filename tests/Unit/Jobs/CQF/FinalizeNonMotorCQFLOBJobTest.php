<?php

declare(strict_types=1);

use App\Enums\ProcessStatusCode;
use App\Jobs\CQF\FinalizeNonMotorCQFLOBJob;
use App\Models\RenewalsUploadLeads;
use App\Services\CQF\NonMotor\NonCQFRenewalBrevoMailService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
});

test('returns early without changes when lead is not found', function () {
    $job = new FinalizeNonMotorCQFLOBJob(99999);
    $job->handle(Mockery::mock(NonCQFRenewalBrevoMailService::class));

    expect(RenewalsUploadLeads::count())->toBe(0);
});

test('marks lead as completed and sets total_records when quotes were processed', function () {
    $lead = RenewalsUploadLeads::factory()->create(['good' => 3, 'cannot_upload' => 0]);

    (new FinalizeNonMotorCQFLOBJob($lead->id))->handle(Mockery::mock(NonCQFRenewalBrevoMailService::class));

    $lead->refresh();
    expect($lead->status)->toBe(ProcessStatusCode::COMPLETED)
        ->and((int) $lead->total_records)->toBe(3);
});

test('marks lead as deleted when no quotes were processed', function () {
    $lead = RenewalsUploadLeads::factory()->create(['good' => 0, 'cannot_upload' => 0]);

    (new FinalizeNonMotorCQFLOBJob($lead->id))->handle(Mockery::mock(NonCQFRenewalBrevoMailService::class));

    $lead->refresh();
    expect((int) $lead->is_deleted)->toBe(1);
});

test('still marks lead as completed when cannot_upload is greater than zero', function () {
    // getBirdWorkflowUrl returns null (no ApplicationStorage row), so sendViaBird exits early
    $lead = RenewalsUploadLeads::factory()->create(['good' => 2, 'cannot_upload' => 1]);

    (new FinalizeNonMotorCQFLOBJob($lead->id))->handle(Mockery::mock(NonCQFRenewalBrevoMailService::class));

    $lead->refresh();
    expect($lead->status)->toBe(ProcessStatusCode::COMPLETED)
        ->and((int) $lead->total_records)->toBe(3);
});

test('logs error on failure', function () {
    $job = new FinalizeNonMotorCQFLOBJob(99);

    expect(fn () => $job->failed(new RuntimeException('test error')))->not->toThrow(Throwable::class);
});

test('does not overwrite DB state when lead is already completed on retry', function () {
    $lead = RenewalsUploadLeads::factory()->create([
        'good' => 3,
        'cannot_upload' => 0,
        'status' => ProcessStatusCode::COMPLETED,
        'total_records' => 3,
    ]);

    // Simulate a retry: handle() must not reset total_records or status
    (new FinalizeNonMotorCQFLOBJob($lead->id))->handle(Mockery::mock(NonCQFRenewalBrevoMailService::class));

    $lead->refresh();
    expect($lead->status)->toBe(ProcessStatusCode::COMPLETED)
        ->and((int) $lead->total_records)->toBe(3);
});
