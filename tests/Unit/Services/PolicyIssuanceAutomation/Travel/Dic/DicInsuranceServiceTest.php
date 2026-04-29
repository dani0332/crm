<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Services\ApplicationStorageService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicInsuranceService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicResponseHandler;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicStepExecutor;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicValidationService;

beforeEach(function () {
    $this->stepExecutorMock = Mockery::mock(DicStepExecutor::class);
    $this->validationServiceMock = Mockery::mock(DicValidationService::class);
    $this->responseHandlerMock = Mockery::mock(DicResponseHandler::class);

    $this->service = new DicInsuranceService(
        $this->stepExecutorMock,
        $this->validationServiceMock,
        $this->responseHandlerMock,
    );

    mockDicTravelAutomationSettings(true, true);
});

afterEach(function () {
    Mockery::close();
});

function mockDicTravelAutomationSettings(
    bool|string|int|null $automationMain,
    bool|string|int|null $retryTimeoutForTimeoutEnabled,
): void {
    $applicationStorageMock = Mockery::mock(ApplicationStorageService::class);
    $applicationStorageMock->shouldReceive('getValueByKey')
        ->with(ApplicationStorageEnums::ENABLE_DIC_TRAVEL_POLICY_ISSUANCE)
        ->andReturn($automationMain);
    $applicationStorageMock->shouldReceive('getValueByKey')
        ->with(ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_DIC_TRAVEL_POLICY_ISSUANCE)
        ->andReturn($retryTimeoutForTimeoutEnabled);

    app()->instance(ApplicationStorageService::class, $applicationStorageMock);
}

test('policy issuance automation enabled reflects application storage', function () {
    mockDicTravelAutomationSettings(true, false);
    expect($this->service->isPolicyIssuanceAutomationEnabled())->toBeTrue();

    mockDicTravelAutomationSettings(false, false);
    expect($this->service->isPolicyIssuanceAutomationEnabled())->toBeFalse();
});

test('timeout retry toggle reflects application storage', function () {
    mockDicTravelAutomationSettings(true, true);
    expect($this->service->isPolicyIssuanceAutomationRetryEnabledForTimeout())->toBeTrue();

    mockDicTravelAutomationSettings(true, false);
    expect($this->service->isPolicyIssuanceAutomationRetryEnabledForTimeout())->toBeFalse();

    mockDicTravelAutomationSettings(true, '0');
    expect($this->service->isPolicyIssuanceAutomationRetryEnabledForTimeout())->toBeFalse();
});
