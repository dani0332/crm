<?php

use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteTypes;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

it('returns null from init for unsupported health insurer so callers must use nullsafe automation checks', function () {
    $service = new PolicyIssuanceService;

    $automation = $service->init(QuoteTypes::HEALTH->value, InsuranceProvidersEnum::RSA);

    expect($automation)->toBeNull()
        ->and($automation?->isPolicyIssuanceAutomationEnabled() ?? false)->toBeFalse();
});

it('returns null from init when insurer code is null so controllers can pass optional provider safely', function () {
    $service = new PolicyIssuanceService;

    expect($service->init(QuoteTypes::HEALTH->value, null))->toBeNull()
        ->and($service->init(QuoteTypes::CAR->value, null))->toBeNull()
        ->and($service->init(QuoteTypes::TRAVEL->value, null))->toBeNull()
        ->and($service->init(QuoteTypes::CYBER->value, null))->toBeNull();
});
