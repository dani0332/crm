<?php

declare(strict_types=1);

use App\Enums\CarRegistrationType;
use App\Enums\CarVehicleUse;
use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Services\EpEcbService;

/**
 * Helper to create a minimal quote object for getCustomerInfo tests.
 *
 * @param  array<string, mixed>  $attributes
 */
function makeQuote(array $attributes = []): object
{
    $defaults = [
        'registration_type' => CarRegistrationType::PERSONAL,
        'vehicle_use' => CarVehicleUse::PRIVATE,
        'company_name' => null,
        'customer' => null,
        'latestInsured' => null,
    ];

    return (object) array_merge($defaults, $attributes);
}

/**
 * Helper to create a minimal latestInsured object.
 *
 * @param  array<string, mixed>  $attributes
 */
function makeInsured(array $attributes = []): object
{
    $defaults = [
        'first_name' => null,
        'last_name' => null,
        'id_type' => null,
        'id_number' => null,
        'customer_type' => CustomerTypeEnum::Individual,
    ];

    return (object) array_merge($defaults, $attributes);
}

/**
 * Invoke getCustomerInfo (private) on an EpEcbService subclass instance.
 */
function invokeGetCustomerInfo(object $service, string $step): array
{
    $method = new ReflectionMethod($service, 'getCustomerInfo');

    return $method->invoke($service, $step);
}

/**
 * Build a minimal EpEcbService subclass that bypasses the constructor.
 */
function makeEpEcbService(object $quote): object
{
    $service = new class extends EpEcbService
    {
        public function __construct() {}
    };

    $service->quote = $quote;

    return $service;
}

test('company private use with empty insured names splits company name into first and last name', function () {
    $quote = makeQuote([
        'registration_type' => CarRegistrationType::COMPANY,
        'vehicle_use' => CarVehicleUse::PRIVATE,
        'company_name' => 'ABC Technologies Pvt Ltd',
        'latestInsured' => makeInsured([
            'customer_type' => CustomerTypeEnum::Entity,
            'id_type' => GenericRequestEnum::TRADE_LICENSE,
            'id_number' => 'TL-123',
        ]),
    ]);

    $service = makeEpEcbService($quote);
    $result = invokeGetCustomerInfo($service, EpEcbService::STEP_CREATE_POLICY_FROM_QUOTE);

    expect($result['customer_fname'])->toBe('ABC')
        ->and($result['customer_lname'])->toBe('Technologies Pvt Ltd');
});

test('company private use with populated insured names does not override them', function () {
    $quote = makeQuote([
        'registration_type' => CarRegistrationType::COMPANY,
        'vehicle_use' => CarVehicleUse::PRIVATE,
        'company_name' => 'ABC Technologies Pvt Ltd',
        'latestInsured' => makeInsured([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'customer_type' => CustomerTypeEnum::Entity,
        ]),
    ]);

    $service = makeEpEcbService($quote);
    $result = invokeGetCustomerInfo($service, EpEcbService::STEP_CREATE_POLICY_FROM_QUOTE);

    expect($result['customer_fname'])->toBe('John')
        ->and($result['customer_lname'])->toBe('Doe');
});

test('personal registration uses insured first and last name without modification', function () {
    $quote = makeQuote([
        'registration_type' => CarRegistrationType::PERSONAL,
        'vehicle_use' => CarVehicleUse::PRIVATE,
        'company_name' => 'Should Not Be Used',
        'latestInsured' => makeInsured([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]),
    ]);

    $service = makeEpEcbService($quote);
    $result = invokeGetCustomerInfo($service, EpEcbService::STEP_CREATE_POLICY_FROM_QUOTE);

    expect($result['customer_fname'])->toBe('Jane')
        ->and($result['customer_lname'])->toBe('Smith');
});

// Mechanical edge case only: single-token company_name yields empty customer_lname, which would
// fail EpEcbService validatePayload (customer_lname required). Business has confirmed production
// company names for this path are always multi-word (at least one space).
test('company private use with single word company name sets first name to company name and last name to empty string', function () {
    $quote = makeQuote([
        'registration_type' => CarRegistrationType::COMPANY,
        'vehicle_use' => CarVehicleUse::PRIVATE,
        'company_name' => 'Acme',
        'latestInsured' => makeInsured([
            'customer_type' => CustomerTypeEnum::Entity,
            'id_type' => GenericRequestEnum::TRADE_LICENSE,
            'id_number' => 'TL-456',
        ]),
    ]);

    $service = makeEpEcbService($quote);
    $result = invokeGetCustomerInfo($service, EpEcbService::STEP_CREATE_POLICY_FROM_QUOTE);

    expect($result['customer_fname'])->toBe('Acme')
        ->and($result['customer_lname'])->toBe('');
});
