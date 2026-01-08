<?php

declare(strict_types=1);

use App\Enums\AdnicEnum;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicApiService;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicBookPolicyService;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicStepExecutor;

beforeEach(function () {
    $this->apiServiceMock = Mockery::mock(AdnicApiService::class);
    $this->bookPolicyServiceMock = Mockery::mock(AdnicBookPolicyService::class);

    $this->executor = new AdnicStepExecutor(
        $this->apiServiceMock,
        $this->bookPolicyServiceMock
    );
});

afterEach(function () {
    Mockery::close();
});

// CRITICAL TEST: Service initialization
test('step executor initializes correctly', function () {
    expect($this->executor)->toBeInstanceOf(AdnicStepExecutor::class)
        ->and($this->executor)->toHaveProperty('healthInsurerRequest')
        ->and($this->executor)->toHaveProperty('healthInsurerResponse');
});
