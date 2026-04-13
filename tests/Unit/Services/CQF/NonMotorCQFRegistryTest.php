<?php

declare(strict_types=1);

use App\Enums\QuoteTypes;
use App\Services\CQF\Contracts\CQFQuoteMappingInterface;
use App\Services\CQF\Contracts\CQFQuoteStorageInterface;
use App\Services\CQF\Contracts\CQFValidationInterface;
use App\Services\CQF\NonMotor\NonMotorCQFRegistry;

beforeEach(function () {
    $this->registry = app(NonMotorCQFRegistry::class);
});

it('returns supported LOBs matching NonMotorCQFRegistry lobMap', function () {
    $lobs = NonMotorCQFRegistry::supportedLOBs();

    $expected = [
        QuoteTypes::BIKE,
        QuoteTypes::YACHT,
        QuoteTypes::CYCLE,
        QuoteTypes::PET,
        QuoteTypes::HOME,
        QuoteTypes::BUSINESS,
    ];

    expect($lobs)->toBeArray()
        ->and($lobs)->toHaveCount(6);

    foreach ($expected as $lob) {
        expect($lobs)->toContain($lob);
    }
});

it('has LOB for Bike', function () {
    expect($this->registry->hasLOB(QuoteTypes::BIKE))->toBeTrue();
});

it('returns validator for Bike', function () {
    $validator = $this->registry->getValidator(QuoteTypes::BIKE);

    expect($validator)->not->toBeNull()
        ->and($validator)->toBeInstanceOf(CQFValidationInterface::class);
});

it('returns mapper for Bike', function () {
    $mapper = $this->registry->getMapper(QuoteTypes::BIKE);

    expect($mapper)->not->toBeNull()
        ->and($mapper)->toBeInstanceOf(CQFQuoteMappingInterface::class);
});

it('returns storage for Bike', function () {
    $storage = $this->registry->getStorage(QuoteTypes::BIKE);

    expect($storage)->not->toBeNull()
        ->and($storage)->toBeInstanceOf(CQFQuoteStorageInterface::class);
});

it('returns null for unsupported LOB when not in registry', function () {
    // Health is excluded from Non-motor CQF Phase 1
    expect($this->registry->hasLOB(QuoteTypes::HEALTH))->toBeFalse();

    $validator = $this->registry->getValidator(QuoteTypes::HEALTH);
    expect($validator)->toBeNull();
});
