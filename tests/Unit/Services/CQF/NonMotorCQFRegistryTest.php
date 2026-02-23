<?php

declare(strict_types=1);

use App\Enums\QuoteTypes;
use App\Services\CQF\NonMotor\NonMotorCQFRegistry;

beforeEach(function () {
    $this->registry = app(NonMotorCQFRegistry::class);
});

it('returns supported LOBs including Bike, Yacht, Jetski, Cycle, Pet, Home, Life, Business, Savings', function () {
    $lobs = NonMotorCQFRegistry::supportedLOBs();

    $expected = [
        QuoteTypes::BIKE,
        QuoteTypes::YACHT,
        QuoteTypes::JETSKI,
        QuoteTypes::CYCLE,
        QuoteTypes::PET,
        QuoteTypes::HOME,
        QuoteTypes::LIFE,
        QuoteTypes::BUSINESS,
        QuoteTypes::SAVINGS,
    ];

    expect($lobs)->toBeArray()
        ->and($lobs)->toHaveCount(9);

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
        ->and($validator)->toBeInstanceOf(\App\Services\CQF\Contracts\CQFValidationInterface::class);
});

it('returns mapper for Bike', function () {
    $mapper = $this->registry->getMapper(QuoteTypes::BIKE);

    expect($mapper)->not->toBeNull()
        ->and($mapper)->toBeInstanceOf(\App\Services\CQF\Contracts\CQFQuoteMappingInterface::class);
});

it('returns storage for Bike', function () {
    $storage = $this->registry->getStorage(QuoteTypes::BIKE);

    expect($storage)->not->toBeNull()
        ->and($storage)->toBeInstanceOf(\App\Services\CQF\Contracts\CQFQuoteStorageInterface::class);
});

it('returns null for unsupported LOB when not in registry', function () {
    // Health is excluded from Non-motor CQF Phase 1
    expect($this->registry->hasLOB(QuoteTypes::HEALTH))->toBeFalse();

    $validator = $this->registry->getValidator(QuoteTypes::HEALTH);
    expect($validator)->toBeNull();
});
