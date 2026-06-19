<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\PolicyIssuanceEnum;
use App\Services\ApplicationStorageService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicInsuranceService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicResponseHandler;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicStepExecutor;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicValidationService;

beforeEach(function () {
    $this->stepExecutorMock = Mockery::mock(DicStepExecutor::class);
    $this->validationServiceMock = Mockery::mock(DicValidationService::class);
    $this->responseHandlerMock = Mockery::mock(DicResponseHandler::class);
    $this->policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);

    $this->service = new DicInsuranceService(
        $this->stepExecutorMock,
        $this->validationServiceMock,
        $this->responseHandlerMock,
        $this->policyIssuanceServiceMock,
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

test('getNextStep proceeds from broker invoice to book policy', function () {
    expect($this->service->getNextStep(PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE))
        ->toBe(PolicyIssuanceEnum::DIC_TRAVEL_BOOK_POLICY);
});

test('getNextStep returns null when book policy is the last completed step', function () {
    expect($this->service->getNextStep(PolicyIssuanceEnum::DIC_TRAVEL_BOOK_POLICY))->toBeNull();
});
