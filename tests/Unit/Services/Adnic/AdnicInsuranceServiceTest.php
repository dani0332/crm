<?php

declare(strict_types=1);

use App\Enums\AdnicEnum;
use App\Enums\ApplicationStorageEnums;
use App\Services\ApplicationStorageService;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicBookPolicyService;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicInsuranceService;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicResponseHandler;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicStepExecutor;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicValidationService;

beforeEach(function () {
    $this->stepExecutorMock = Mockery::mock(AdnicStepExecutor::class);
    $this->validationServiceMock = Mockery::mock(AdnicValidationService::class);
    $this->bookPolicyServiceMock = Mockery::mock(AdnicBookPolicyService::class);
    $this->responseHandlerMock = Mockery::mock(AdnicResponseHandler::class);

    $this->service = new AdnicInsuranceService(
        $this->stepExecutorMock,
        $this->validationServiceMock,
        $this->bookPolicyServiceMock,
        $this->responseHandlerMock
    );

    // Mock ApplicationStorageService
    mockAutomationEnabled(true);
});

afterEach(function () {
    Mockery::close();
});

/**
 * Helper function to mock automation settings
 */
function mockAutomationEnabled(bool $enabled, bool $retryEnabled = true): void
{
    $applicationStorageMock = Mockery::mock(ApplicationStorageService::class);
    $applicationStorageMock->shouldReceive('getValueByKey')
        ->with(ApplicationStorageEnums::ENABLE_ADNIC_HEALTH_POLICY_ISSUANCE)
        ->andReturn($enabled);
    $applicationStorageMock->shouldReceive('getValueByKey')
        ->with(ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_ADNIC_HEALTH_POLICY_ISSUANCE)
        ->andReturn($retryEnabled);

    app()->instance(ApplicationStorageService::class, $applicationStorageMock);
}

// CRITICAL TEST: Automation configuration
test('automation is enabled returns correct value', function () {
    mockAutomationEnabled(true);
    expect($this->service->isPolicyIssuanceAutomationEnabled())->toBeTrue();

    mockAutomationEnabled(false);
    expect($this->service->isPolicyIssuanceAutomationEnabled())->toBeFalse();
});

// CRITICAL TEST: Retry configuration  
test('automation retry for timeout is enabled returns correct value', function () {
    mockAutomationEnabled(true, true);
    expect($this->service->isPolicyIssuanceAutomationRetryEnabledForTimeout())->toBeTrue();

    mockAutomationEnabled(true, false);
    expect($this->service->isPolicyIssuanceAutomationRetryEnabledForTimeout())->toBeFalse();
});

// CRITICAL TEST: Step sequence logic
test('get next step returns correct sequence', function () {
    expect($this->service->getNextStep(null))->toBe(AdnicEnum::STEP_UPLOAD_DOCUMENTS)
        ->and($this->service->getNextStep(AdnicEnum::STEP_UPLOAD_DOCUMENTS))->toBe(AdnicEnum::STEP_ISSUE_POLICY)
        ->and($this->service->getNextStep(AdnicEnum::STEP_ISSUE_POLICY))->toBe(AdnicEnum::STEP_UPLOAD_POLICY_DOCS)
        ->and($this->service->getNextStep(AdnicEnum::STEP_UPLOAD_POLICY_DOCS))->toBeNull()
        ->and($this->service->getNextStep('InvalidStep'))->toBeNull();
});
