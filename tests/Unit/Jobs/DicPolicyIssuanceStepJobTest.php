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

    $travelQuote = TravelQuote::factory()->create([
        'code' => 'TRV-ASYNC-EX',
    ]);

    $process = PolicyIssuance::factory()
        ->forQuote($travelQuote)
        ->processing()
        ->create([
            'insurance_provider_id' => $provider->id,
            'quote_type' => QuoteTypes::TRAVEL->value,
            'completed_step' => null,
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

it('does not double-count attempt logs when step persisted issuance log then throws retryable exception', function (): void {
    Bus::fake();

    $provider = InsuranceProvider::factory()->create([
        'code' => InsuranceProviderEnum::DIC->value,
        'text' => 'DIC Test',
    ]);

    $travelQuote = TravelQuote::factory()->create([
        'code' => 'TRV-DIC-NO-DUP-LOG',
    ]);

    $process = PolicyIssuance::factory()
        ->forQuote($travelQuote)
        ->processing()
        ->create([
            'insurance_provider_id' => $provider->id,
            'quote_type' => QuoteTypes::TRAVEL->value,
            'completed_step' => null,
        ]);

    $dic = Mockery::mock(DicInsuranceService::class);
    $dic->shouldReceive('resolveAsyncStepToRun')->once()->andReturn(PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY);
    $dic->shouldReceive('isPolicyIssuanceAutomationEnabled')->once()->andReturn(true);
    $dic->shouldReceive('validateBeforeDicAsyncRun')->once()->andReturn(['status' => true]);
    $dic->shouldReceive('runSingleDicAsyncStep')
        ->once()
        ->andReturnUsing(function () use ($process): never {
            PolicyIssuanceLog::factory()->create([
                'policy_issuance_id' => $process->id,
                'step' => PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
                'status' => PolicyIssuanceEnum::SUCCESS_STATUS,
                'endPoint' => 'https://unit-dic.test/products/buy/client',
            ]);

            throw new IlluminateConnectionException('retryable after issuance log persisted');
        });

    app()->instance(DicInsuranceService::class, $dic);

    (new DicPolicyIssuanceStepJob($process->id, PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY))
        ->handle(app(DicInsuranceService::class), app(PolicyIssuanceService::class));

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

    $travelQuote = TravelQuote::factory()->create([
        'code' => 'TRV-NO-RETRY',
    ]);

    $process = PolicyIssuance::factory()
        ->forQuote($travelQuote)
        ->processing()
        ->create([
            'insurance_provider_id' => $provider->id,
            'quote_type' => QuoteTypes::TRAVEL->value,
            'completed_step' => null,
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
    $policyIssuanceService->shouldReceive('applyTravelDicAutomationResult')->once();
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

    $travelQuote = TravelQuote::factory()->create([
        'code' => 'TRV-ASYNC-MAX',
    ]);

    $process = PolicyIssuance::factory()
        ->forQuote($travelQuote)
        ->processing()
        ->create([
            'insurance_provider_id' => $provider->id,
            'quote_type' => QuoteTypes::TRAVEL->value,
            'completed_step' => null,
        ]);

    PolicyIssuanceLog::factory()
        ->count(3)
        ->create([
            'policy_issuance_id' => $process->id,
            'step' => PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
        ]);

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
    $policyIssuanceService->shouldReceive('applyTravelDicAutomationResult')->once();
    app()->instance(PolicyIssuanceService::class, $policyIssuanceService);

    (new DicPolicyIssuanceStepJob($process->id, PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY))
        ->handle(app(DicInsuranceService::class), app(PolicyIssuanceService::class));

    expect($process->fresh()->status)->toBe(PolicyIssuanceEnum::FAILED_STATUS);

    Bus::assertNothingDispatched();
});
