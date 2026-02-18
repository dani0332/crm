<?php

declare(strict_types=1);

use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicValidationService;

beforeEach(function () {
    $this->service = new AdnicValidationService;
});

afterEach(function () {
    Mockery::close();
});

// CRITICAL TEST: Service initialization
test('validation service initializes correctly', function () {
    expect($this->service)->toBeInstanceOf(AdnicValidationService::class);
});
