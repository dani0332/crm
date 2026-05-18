<?php

declare(strict_types=1);

use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicApiService;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicResponseHandler;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicStepExecutor;

beforeEach(function () {
    $this->apiServiceMock = Mockery::mock(AdnicApiService::class);
    $this->responseHandlerMock = Mockery::mock(AdnicResponseHandler::class);

    $this->executor = new AdnicStepExecutor(
        $this->apiServiceMock,
        $this->responseHandlerMock,
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
