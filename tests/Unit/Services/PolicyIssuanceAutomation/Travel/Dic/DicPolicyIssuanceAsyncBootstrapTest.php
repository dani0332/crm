<?php

declare(strict_types=1);

use App\Enums\PolicyIssuanceEnum;
use App\Jobs\DicPolicyIssuanceStepJob;
use App\Models\PolicyIssuance;
use App\Models\TravelQuote;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicInsuranceService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicPolicyIssuanceAsyncBootstrap;
use Illuminate\Support\Facades\Bus;

afterEach(function (): void {
    Mockery::close();
});

it('marks issuance failed when dic automation is disabled', function (): void {
    $dic = Mockery::mock(DicInsuranceService::class);
    $dic->shouldReceive('isPolicyIssuanceAutomationEnabled')->once()->andReturn(false);

    $process = Mockery::mock(PolicyIssuance::class);
    $process->shouldReceive('getAttribute')->with('id')->andReturn(42);
    $process->shouldReceive('getAttribute')->with('model')->andReturn(null);
    $process->shouldReceive('update')->once()->with([
        'status' => PolicyIssuanceEnum::FAILED_STATUS,
        'message' => json_encode(['error' => 'DIC Travel automation is disabled']),
    ]);

    (new DicPolicyIssuanceAsyncBootstrap($dic))->dispatchInitialStepFromOrchestratorJob($process);
});

it('dispatches first async step when quote validates and a step is pending', function (): void {
    Bus::fake();

    $travelQuote = TravelQuote::factory()->make([
        'code' => 'TRV-UNIT',
    ]);

    $dic = Mockery::mock(DicInsuranceService::class);
    $dic->shouldReceive('isPolicyIssuanceAutomationEnabled')->once()->andReturn(true);
    $dic->shouldReceive('validateBeforeDicAsyncRun')->once()->with(Mockery::on(function (TravelQuote $q): bool {
        return $q->code === 'TRV-UNIT';
    }))->andReturn(['status' => true]);
    $dic->shouldReceive('resolveAsyncStepToRun')->once()->andReturn(PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY);

    $process = new PolicyIssuance;
    $process->id = 99;
    $process->syncOriginal();
    $process->exists = true;
    $process->setRelation('model', $travelQuote);

    (new DicPolicyIssuanceAsyncBootstrap($dic))->dispatchInitialStepFromOrchestratorJob($process);

    Bus::assertDispatched(function (DicPolicyIssuanceStepJob $job): bool {
        return $job->policyIssuanceId === 99
            && $job->step === PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY;
    });
});
