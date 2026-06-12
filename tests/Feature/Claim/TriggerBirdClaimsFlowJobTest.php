<?php

declare(strict_types=1);

use App\Jobs\Claim\TriggerBirdClaimsFlowJob;
use App\Models\ClaimRequest;
use App\Observers\ClaimRequestObserver;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

test('observer dispatches TriggerBirdClaimsFlowJob when policy_number changes from empty to new value', function () {
    Queue::fake();

    $claim = new ClaimRequest;
    $claim->uuid = Str::uuid()->toString();
    $claim->quote_type_id = 1;
    $claim->manager_id = 1;
    $claim->insurance_provider_id = 1;

    // Simulate the model knowing its original (empty) policy_number
    $claim->syncOriginal();
    $claim->policy_number = 'POL-001';

    $observer = app(ClaimRequestObserver::class);
    $observer->updating($claim);

    Queue::assertPushed(TriggerBirdClaimsFlowJob::class);
});

test('observer does not dispatch TriggerBirdClaimsFlowJob when policy_number is unchanged', function () {
    Queue::fake();

    $claim = new ClaimRequest;
    $claim->uuid = Str::uuid()->toString();
    $claim->quote_type_id = 1;
    $claim->manager_id = 1;
    $claim->insurance_provider_id = 1;
    $claim->policy_number = 'POL-001';
    $claim->syncOriginal();

    // No change to policy_number
    $observer = app(ClaimRequestObserver::class);
    $observer->updating($claim);

    Queue::assertNotPushed(TriggerBirdClaimsFlowJob::class);
});

test('TriggerBirdClaimsFlowJob runs after transaction commit', function () {
    $job = new TriggerBirdClaimsFlowJob(Str::uuid()->toString());

    // afterCommit() sets this flag so the job is held until the transaction commits
    expect($job->afterCommit)->toBeTrue();
});
