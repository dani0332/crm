<?php

use App\Enums\AwnicEnum;
use App\Models\PersonalQuote;
use App\Services\ApplicationStorageService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwniInsuranceService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicBookPolicyService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicResponseHandler;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicStepExecutor;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicValidationService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\FakeApplicationStorageService;
use Tests\Support\FakePolicyIssuanceProcess;
use Tests\Support\FakePolicyIssuanceService;

beforeEach(function () {
    TestSchemaCreator::createCyberSchema();

    app()->instance(ApplicationStorageService::class, new FakeApplicationStorageService());
    app()->instance(PolicyIssuanceService::class, new FakePolicyIssuanceService());
});

afterEach(function () {
    Mockery::close();
});

it('executes all AWNIC steps when validation passes', function () {
    $quote = PersonalQuote::factory()->cyberQuote()->withCyberDependencies()->create();
    dd($quote);
    $process = new FakePolicyIssuanceProcess($quote);

    $validation = Mockery::mock(AwnicValidationService::class);
    $validation
        ->shouldReceive('validateRequiredData')
        ->once()
        ->with($quote)
        ->andReturn(['status' => true]);

    $stepExecutor = Mockery::mock(AwnicStepExecutor::class);
    $stepExecutor->shouldReceive('executeIssuePolicyStep')->once()->andReturn([
        'status' => true,
        'completed_step' => AwnicEnum::STEP_ISSUE_POLICY,
    ]);
    $stepExecutor->shouldReceive('executeUploadDocumentsStep')->once()->andReturn([
        'status' => true,
        'completed_step' => AwnicEnum::STEP_UPLOAD_DOCUMENTS,
    ]);
    $stepExecutor->shouldReceive('executeUploadPolicyDocumentsStep')->once()->andReturn([
        'status' => true,
        'completed_step' => AwnicEnum::STEP_UPLOAD_POLICY_DOCS,
    ]);
    $stepExecutor->shouldReceive('executeBookPolicyStep')->once()->andReturn([
        'status' => true,
        'completed_step' => AwnicEnum::STEP_BOOK_POLICY,
    ]);

    $service = new AwniInsuranceService(
        $stepExecutor,
        $validation,
        Mockery::mock(AwnicBookPolicyService::class),
        new AwnicResponseHandler()
    );

    $result = $service->executeSteps($process);

    expect($result['status'])->toBeTrue()
        ->and($process->completed_step)->toBe(AwnicEnum::STEP_BOOK_POLICY);
});

it('stops execution when validation fails', function () {
    $quote = PersonalQuote::factory()->withCyberDependencies()->create();
    $process = new FakePolicyIssuanceProcess($quote);

    $validation = Mockery::mock(AwnicValidationService::class);
    $validation
        ->shouldReceive('validateRequiredData')
        ->once()
        ->with($quote)
        ->andReturn(['status' => false, 'error' => 'Missing data', 'message' => 'Missing data']);

    $stepExecutor = Mockery::mock(AwnicStepExecutor::class);
    $stepExecutor->shouldNotReceive('executeIssuePolicyStep');

    $service = new AwniInsuranceService(
        $stepExecutor,
        $validation,
        Mockery::mock(AwnicBookPolicyService::class),
        new AwnicResponseHandler()
    );

    $result = $service->executeSteps($process);

    expect($result['status'])->toBeFalse()
        ->and($result['error'])->toBe('Missing data');
});

it('propagates step failure immediately', function () {
    $quote = PersonalQuote::factory()->withCyberDependencies()->create();
    $process = new FakePolicyIssuanceProcess($quote);

    $validation = Mockery::mock(AwnicValidationService::class);
    $validation
        ->shouldReceive('validateRequiredData')
        ->once()
        ->andReturn(['status' => true]);

    $stepExecutor = Mockery::mock(AwnicStepExecutor::class);
    $stepExecutor->shouldReceive('executeIssuePolicyStep')->once()->andReturn([
        'status' => false,
        'error' => 'API failure',
        'message' => 'api fail',
    ]);
    $stepExecutor->shouldNotReceive('executeUploadDocumentsStep');

    $service = new AwniInsuranceService(
        $stepExecutor,
        $validation,
        Mockery::mock(AwnicBookPolicyService::class),
        new AwnicResponseHandler()
    );

    $result = $service->executeSteps($process);

    expect($result['status'])->toBeFalse()
        ->and($result['error'])->toBe('API failure');
});

