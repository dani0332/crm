<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\InsuranceProviderEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Jobs\DicPolicyIssuanceStepJob;
use App\Models\ApplicationStorage;
use App\Models\InsuranceProvider;
use App\Models\PolicyIssuance;
use App\Models\PolicyIssuanceLog;
use App\Models\TravelQuote;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicInsuranceService;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Http\Client\ConnectionException as IlluminateConnectionException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

afterEach(function (): void {
    Mockery::close();
    app()->forgetInstance(PolicyIssuanceService::class);
    app()->forgetInstance(DicInsuranceService::class);
});

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();
});

it('sets unique lock window from application storage retry delay', function (): void {
    ApplicationStorage::factory()->create([
        'key_name' => ApplicationStorageEnums::DIC_TRAVEL_ASYNC_RETRY_DELAY_SECONDS,
        'value' => '100',
        'is_active' => 1,
    ]);

    $job = new DicPolicyIssuanceStepJob(1, 'IssuePolicy');

    expect($job->uniqueFor)->toBe(130);
});

it('defaults unique lock window when retry delay storage is missing', function (): void {
    $job = new DicPolicyIssuanceStepJob(1, 'IssuePolicy');

    expect($job->uniqueFor)->toBe(120);
});

it('uses unique-until-processing so in-handle retry dispatches are not swallowed by ShouldBeUnique', function (): void {
    $job = new DicPolicyIssuanceStepJob(1, 'IssuePolicy');

    expect($job)->toBeInstanceOf(ShouldBeUniqueUntilProcessing::class);
});

it('schedules delayed async step retry when runSingleDicAsyncStep throws', function (): void {
    Bus::fake();

    $provider = InsuranceProvider::factory()->create([
        'code' => InsuranceProviderEnum::DIC->value,
        'text' => 'DIC Test',
    ]);

    $travelId = DB::table('travel_quote_request')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'code' => 'TRV-ASYNC-EX',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $process = PolicyIssuance::query()->create([
        'insurance_provider_id' => $provider->id,
        'model_type' => TravelQuote::class,
        'model_id' => $travelId,
        'quote_type' => QuoteTypes::TRAVEL->value,
        'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
        'completed_step' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $dic = Mockery::mock(DicInsuranceService::class);
    $dic->shouldReceive('resolveAsyncStepToRun')->once()->andReturn(PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY);
    $dic->shouldReceive('isPolicyIssuanceAutomationEnabled')->once()->andReturn(true);
    $dic->shouldReceive('validateBeforeDicAsyncRun')->once()->andReturn(['status' => true]);
    $dic->shouldReceive('runSingleDicAsyncStep')->once()->andThrow(new IlluminateConnectionException('cURL error 28: Timeout'));

    app()->instance(DicInsuranceService::class, $dic);

    (new DicPolicyIssuanceStepJob($process->id, PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY))
        ->handle(app(DicInsuranceService::class), app(PolicyIssuanceService::class));

    expect($process->fresh()->status)->toBe(PolicyIssuanceEnum::PROCESSING_STATUS);

    expect(
        PolicyIssuanceLog::query()
            ->where('policy_issuance_id', $process->id)
            ->where('step', PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY)
            ->count()
    )->toBe(1);

    Bus::assertDispatched(function (DicPolicyIssuanceStepJob $job) use ($process): bool {
        return $job->policyIssuanceId === $process->id
            && $job->step === PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY;
    });
});

it('fails issuance immediately without retry when runSingleDicAsyncStep throws non-transport exception', function (): void {
    Bus::fake();

    $provider = InsuranceProvider::factory()->create([
        'code' => InsuranceProviderEnum::DIC->value,
        'text' => 'DIC Test',
    ]);

    $travelId = DB::table('travel_quote_request')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'code' => 'TRV-NO-RETRY',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $process = PolicyIssuance::query()->create([
        'insurance_provider_id' => $provider->id,
        'model_type' => TravelQuote::class,
        'model_id' => $travelId,
        'quote_type' => QuoteTypes::TRAVEL->value,
        'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
        'completed_step' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $dic = Mockery::mock(DicInsuranceService::class);
    $dic->shouldReceive('resolveAsyncStepToRun')->once()->andReturn(PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY);
    $dic->shouldReceive('isPolicyIssuanceAutomationEnabled')->once()->andReturn(true);
    $dic->shouldReceive('validateBeforeDicAsyncRun')->once()->andReturn(['status' => true]);
    $dic->shouldReceive('runSingleDicAsyncStep')->once()->andThrow(new RuntimeException('bug in automation code'));

    $dic->shouldReceive('resolveTravelDicAsyncFailureContext')
        ->once()
        ->with(PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY, Mockery::type('array'))
        ->andReturn([
            'insurer_api_status_id' => PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID,
            'process_involved' => PolicyIssuanceEnum::PROCESS_INVOLVED_ISSUE_POLICY,
        ]);

    app()->instance(DicInsuranceService::class, $dic);

    $policyIssuanceService = Mockery::mock(PolicyIssuanceService::class);
    $policyIssuanceService->shouldReceive('applyTravelDicAutomationFailure')->once();
    app()->instance(PolicyIssuanceService::class, $policyIssuanceService);

    (new DicPolicyIssuanceStepJob($process->id, PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY))
        ->handle(app(DicInsuranceService::class), app(PolicyIssuanceService::class));

    expect($process->fresh()->status)->toBe(PolicyIssuanceEnum::FAILED_STATUS);

    Bus::assertNothingDispatched();
});

it('marks issuance failed after max attempts when runSingleDicAsyncStep throws', function (): void {
    Bus::fake();

    $provider = InsuranceProvider::factory()->create([
        'code' => InsuranceProviderEnum::DIC->value,
        'text' => 'DIC Test',
    ]);

    $travelId = DB::table('travel_quote_request')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'code' => 'TRV-ASYNC-MAX',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $process = PolicyIssuance::query()->create([
        'insurance_provider_id' => $provider->id,
        'model_type' => TravelQuote::class,
        'model_id' => $travelId,
        'quote_type' => QuoteTypes::TRAVEL->value,
        'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
        'completed_step' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    for ($i = 0; $i < 3; $i++) {
        DB::table('policy_issuance_logs')->insert([
            'policy_issuance_id' => $process->id,
            'model_type' => null,
            'model_id' => null,
            'step' => PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
            'status' => null,
            'payload' => null,
            'response' => null,
            'endPoint' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $dic = Mockery::mock(DicInsuranceService::class);
    $dic->shouldReceive('resolveAsyncStepToRun')->once()->andReturn(PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY);
    $dic->shouldReceive('isPolicyIssuanceAutomationEnabled')->once()->andReturn(true);
    $dic->shouldReceive('validateBeforeDicAsyncRun')->once()->andReturn(['status' => true]);
    $dic->shouldReceive('runSingleDicAsyncStep')->once()->andThrow(new IlluminateConnectionException('cURL error 7: Failed to connect'));
    $dic->shouldReceive('resolveTravelDicAsyncFailureContext')
        ->once()
        ->with(PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY, Mockery::type('array'))
        ->andReturn([
            'insurer_api_status_id' => PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID,
            'process_involved' => PolicyIssuanceEnum::PROCESS_INVOLVED_ISSUE_POLICY,
        ]);

    app()->instance(DicInsuranceService::class, $dic);

    $policyIssuanceService = Mockery::mock(PolicyIssuanceService::class);
    $policyIssuanceService->shouldReceive('storePolicyIssuanceLog')->once();
    $policyIssuanceService->shouldReceive('applyTravelDicAutomationFailure')->once();
    app()->instance(PolicyIssuanceService::class, $policyIssuanceService);

    (new DicPolicyIssuanceStepJob($process->id, PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY))
        ->handle(app(DicInsuranceService::class), app(PolicyIssuanceService::class));

    expect($process->fresh()->status)->toBe(PolicyIssuanceEnum::FAILED_STATUS);

    Bus::assertNothingDispatched();
});
