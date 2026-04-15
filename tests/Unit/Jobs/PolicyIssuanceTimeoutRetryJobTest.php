<?php

use App\Enums\ApplicationStorageEnums;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Jobs\PolicyIssuanceTimeoutRetryJob;
use App\Models\InsuranceProvider;
use App\Models\PolicyIssuance;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicInsuranceService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    foreach ([
        ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_ADNIC_HEALTH_POLICY_ISSUANCE => '1',
        ApplicationStorageEnums::ADNIC_NUMBER_OF_ALLOWED_RETRY_FOR_TIMEOUT => '2',
        ApplicationStorageEnums::ADNIC_POLICY_ISSUANCE_TIMEOUT_RETRY_COOLDOWN_MINUTES => '5',
    ] as $key => $value) {
        DB::table('application_storage')->updateOrInsert(
            ['key_name' => $key],
            [
                'value' => $value,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
});

it('resets timeout to pending and increments retry_count when under the configured max', function () {
    $provider = InsuranceProvider::factory()->adnic()->create();

    $id = DB::table('policy_issuance')->insertGetId([
        'insurance_provider_id' => $provider->id,
        'model_type' => 'App\Models\HealthQuote',
        'model_id' => 1,
        'quote_type' => QuoteTypes::HEALTH->value,
        'status' => PolicyIssuanceEnum::TIMEOUT_STATUS,
        'completed_step' => null,
        'message' => null,
        'retry_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    PolicyIssuanceTimeoutRetryJob::dispatchSync($id);

    $row = PolicyIssuance::query()->find($id);
    expect($row->status)->toBe(PolicyIssuanceEnum::PENDING_STATUS)
        ->and($row->retry_count)->toBe(1);
});

it('does not update when retry_count has reached the maximum allowed', function () {
    $provider = InsuranceProvider::factory()->adnic()->create();

    $id = DB::table('policy_issuance')->insertGetId([
        'insurance_provider_id' => $provider->id,
        'model_type' => 'App\Models\HealthQuote',
        'model_id' => 1,
        'quote_type' => QuoteTypes::HEALTH->value,
        'status' => PolicyIssuanceEnum::TIMEOUT_STATUS,
        'completed_step' => null,
        'message' => null,
        'retry_count' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    PolicyIssuanceTimeoutRetryJob::dispatchSync($id);

    $row = PolicyIssuance::query()->find($id);
    expect($row->status)->toBe(PolicyIssuanceEnum::TIMEOUT_STATUS)
        ->and($row->retry_count)->toBe(2);
});

it('does not update when retry automation is disabled', function () {
    DB::table('application_storage')->where('key_name', ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_ADNIC_HEALTH_POLICY_ISSUANCE)->update(['value' => '0']);

    $provider = InsuranceProvider::factory()->adnic()->create();

    $id = DB::table('policy_issuance')->insertGetId([
        'insurance_provider_id' => $provider->id,
        'model_type' => 'App\Models\HealthQuote',
        'model_id' => 1,
        'quote_type' => QuoteTypes::HEALTH->value,
        'status' => PolicyIssuanceEnum::TIMEOUT_STATUS,
        'completed_step' => null,
        'message' => null,
        'retry_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    PolicyIssuanceTimeoutRetryJob::dispatchSync($id);

    $row = PolicyIssuance::query()->find($id);
    expect($row->status)->toBe(PolicyIssuanceEnum::TIMEOUT_STATUS)
        ->and($row->retry_count)->toBe(0);
});

it('does not update when status is no longer timeout', function () {
    $provider = InsuranceProvider::factory()->adnic()->create();

    $id = DB::table('policy_issuance')->insertGetId([
        'insurance_provider_id' => $provider->id,
        'model_type' => 'App\Models\HealthQuote',
        'model_id' => 1,
        'quote_type' => QuoteTypes::HEALTH->value,
        'status' => PolicyIssuanceEnum::PENDING_STATUS,
        'completed_step' => null,
        'message' => null,
        'retry_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    PolicyIssuanceTimeoutRetryJob::dispatchSync($id);

    $row = PolicyIssuance::query()->find($id);
    expect($row->status)->toBe(PolicyIssuanceEnum::PENDING_STATUS)
        ->and($row->retry_count)->toBe(0);
});

it('schedules the delayed job when handleTimeoutStatusUpdate is called and retry is enabled', function () {
    Bus::fake();

    $provider = InsuranceProvider::factory()->adnic()->create();

    $id = DB::table('policy_issuance')->insertGetId([
        'insurance_provider_id' => $provider->id,
        'model_type' => 'App\Models\HealthQuote',
        'model_id' => 1,
        'quote_type' => QuoteTypes::HEALTH->value,
        'status' => PolicyIssuanceEnum::TIMEOUT_STATUS,
        'completed_step' => null,
        'message' => null,
        'retry_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $policyIssuance = PolicyIssuance::query()->findOrFail($id);

    app(AdnicInsuranceService::class)->handleTimeoutStatusUpdate($policyIssuance);

    Bus::assertDispatched(PolicyIssuanceTimeoutRetryJob::class);
});

it('does not schedule the job when retry automation is disabled', function () {
    Bus::fake();

    DB::table('application_storage')->where('key_name', ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_ADNIC_HEALTH_POLICY_ISSUANCE)->update(['value' => '0']);

    $provider = InsuranceProvider::factory()->adnic()->create();

    $id = DB::table('policy_issuance')->insertGetId([
        'insurance_provider_id' => $provider->id,
        'model_type' => 'App\Models\HealthQuote',
        'model_id' => 1,
        'quote_type' => QuoteTypes::HEALTH->value,
        'status' => PolicyIssuanceEnum::TIMEOUT_STATUS,
        'completed_step' => null,
        'message' => null,
        'retry_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $policyIssuance = PolicyIssuance::query()->findOrFail($id);

    app(AdnicInsuranceService::class)->handleTimeoutStatusUpdate($policyIssuance);

    Bus::assertNotDispatched(PolicyIssuanceTimeoutRetryJob::class);
});
