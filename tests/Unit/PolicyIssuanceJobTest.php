<?php

use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Jobs\PolicyIssuanceJob;
use App\Models\InsuranceProvider;
use App\Models\PolicyIssuance;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

it('sets status to timeout when failed with MaxAttemptsExceededException', function () {
    $provider = InsuranceProvider::factory()->adnic()->create();

    $id = DB::table('policy_issuance')->insertGetId([
        'insurance_provider_id' => $provider->id,
        'model_type' => 'App\Models\HealthQuote',
        'model_id' => 1,
        'quote_type' => QuoteTypes::HEALTH->value,
        'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
        'completed_step' => null,
        'message' => null,
        'retry_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $job = new PolicyIssuanceJob($id);
    $exception = new MaxAttemptsExceededException('App\Jobs\PolicyIssuanceJob has been attempted too many times.');

    $job->failed($exception);

    $row = PolicyIssuance::query()->find($id);
    expect($row->status)->toBe(PolicyIssuanceEnum::TIMEOUT_STATUS)
        ->and(json_decode($row->message, true)['error'])->toBe($exception->getMessage());
});

it('sets status to timeout when failed with a cURL timeout error', function () {
    $provider = InsuranceProvider::factory()->adnic()->create();

    $id = DB::table('policy_issuance')->insertGetId([
        'insurance_provider_id' => $provider->id,
        'model_type' => 'App\Models\HealthQuote',
        'model_id' => 1,
        'quote_type' => QuoteTypes::HEALTH->value,
        'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
        'completed_step' => null,
        'message' => null,
        'retry_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $job = new PolicyIssuanceJob($id);

    $job->failed(new RuntimeException('cURL error 28: Operation timed out after 30000 milliseconds'));

    $row = PolicyIssuance::query()->find($id);
    expect($row->status)->toBe(PolicyIssuanceEnum::TIMEOUT_STATUS);
});

it('sets status to failed when failed with a non-timeout exception', function () {
    $provider = InsuranceProvider::factory()->adnic()->create();

    $id = DB::table('policy_issuance')->insertGetId([
        'insurance_provider_id' => $provider->id,
        'model_type' => 'App\Models\HealthQuote',
        'model_id' => 1,
        'quote_type' => QuoteTypes::HEALTH->value,
        'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
        'completed_step' => null,
        'message' => null,
        'retry_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $job = new PolicyIssuanceJob($id);

    $job->failed(new RuntimeException('Something went wrong in the API'));

    $row = PolicyIssuance::query()->find($id);
    expect($row->status)->toBe(PolicyIssuanceEnum::FAILED_STATUS);
});

it('has failOnTimeout enabled to prevent orphaned Redis reservations on worker kill', function () {
    $job = new PolicyIssuanceJob(0);

    expect($job->failOnTimeout)->toBeTrue();
});

it('uses redis_policy_issuance connection whose retry_after exceeds the job timeout', function () {
    $job = new PolicyIssuanceJob(0);

    // $connection is set via onConnection() in the constructor
    expect($job->connection)->toBe('redis_policy_issuance');

    $retryAfter = config('queue.connections.redis_policy_issuance.retry_after');
    expect($retryAfter)->toBeGreaterThan($job->timeout);
});

it('has uniqueFor greater than retry_after to prevent duplicate dispatches during re-queue window', function () {
    $job = new PolicyIssuanceJob(0);
    $retryAfter = config('queue.connections.redis_policy_issuance.retry_after');

    expect($job->uniqueFor)->toBeGreaterThan($retryAfter);
});
