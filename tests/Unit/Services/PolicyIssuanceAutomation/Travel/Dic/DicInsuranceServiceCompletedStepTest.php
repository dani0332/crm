<?php

declare(strict_types=1);

use App\Enums\PolicyIssuanceEnum;
use App\Models\PolicyIssuance;
use App\Models\TravelQuote;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicInsuranceService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicResponseHandler;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicStepExecutor;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicValidationService;
use Tests\Helpers\TestSchemaCreator;

afterEach(function (): void {
    Mockery::close();
});

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();

    $this->service = new DicInsuranceService(
        Mockery::mock(DicStepExecutor::class),
        Mockery::mock(DicValidationService::class),
        Mockery::mock(DicResponseHandler::class),
        Mockery::mock(PolicyIssuanceService::class),
    );
});

test('updateProcessCompletedStepFromResponse updates when step succeeded with completed_step', function (): void {
    $travelQuote = TravelQuote::factory()->create(['code' => 'TRV-COMPLETE-STEP']);
    $process = PolicyIssuance::factory()
        ->forQuote($travelQuote)
        ->create([
            'completed_step' => PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
        ]);

    $this->service->updateProcessCompletedStepFromResponse($process, [
        'status' => true,
        'completed_step' => PolicyIssuanceEnum::DIC_TRAVEL_BOOK_POLICY,
    ]);

    expect($process->fresh()->completed_step)->toBe(PolicyIssuanceEnum::DIC_TRAVEL_BOOK_POLICY);
});

test('updateProcessCompletedStepFromResponse skips persist when completed_step is null', function (): void {
    $travelQuote = TravelQuote::factory()->create(['code' => 'TRV-NO-COMPLETED-STEP']);
    $previous = PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE;
    $process = PolicyIssuance::factory()
        ->forQuote($travelQuote)
        ->create([
            'completed_step' => $previous,
        ]);

    $this->service->updateProcessCompletedStepFromResponse($process, [
        'status' => true,
        'completed_step' => null,
    ]);

    expect($process->fresh()->completed_step)->toBe($previous);
});

test('updateProcessCompletedStepFromResponse skips persist when status is false', function (): void {
    $travelQuote = TravelQuote::factory()->create(['code' => 'TRV-FAIL-STATUS']);
    $previous = PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE;
    $process = PolicyIssuance::factory()
        ->forQuote($travelQuote)
        ->create([
            'completed_step' => $previous,
        ]);

    $this->service->updateProcessCompletedStepFromResponse($process, [
        'status' => false,
        'completed_step' => PolicyIssuanceEnum::DIC_TRAVEL_BOOK_POLICY,
    ]);

    expect($process->fresh()->completed_step)->toBe($previous);
});
