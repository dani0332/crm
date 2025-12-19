<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(Tests\TestCase::class)->in('Feature');
uses(Tests\TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toBeSuccessResponse', function () {
    return $this->toBeArray()
        ->toHaveKey('status')
        ->and($this->value['status'])->toBeTrue();
});

expect()->extend('toBeFailureResponse', function () {
    return $this->toBeArray()
        ->toHaveKey('status')
        ->and($this->value['status'])->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the amount of code you need to write.
|
*/

/*
|--------------------------------------------------------------------------
| NGI Test Helpers
|--------------------------------------------------------------------------
|
| Helper functions for NGI Device policy issuance tests.
|
*/

/**
 * Create a mock quote object for NGI tests.
 *
 * @param  array  $overrides  Custom field values
 */
function ngiMockQuote(array $overrides = []): object
{
    $defaults = [
        'id' => 1,
        'code' => 'DEV-'.uniqid(),
        'uuid' => 'test-uuid-'.uniqid(),
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'mobile_no' => '+971501234567',
        'insurer_quote_number' => 'NGI-Q-'.uniqid(),
        'policy_start_date' => now()->format('Y-m-d'),
        'policy_expiry_date' => now()->addYear()->format('Y-m-d'),
        'policy_number' => null,
    ];

    return (object) array_merge($defaults, $overrides);
}

/**
 * Create a mock process object for NGI tests.
 *
 * @param  array  $overrides  Custom field values
 */
function ngiMockProcess(array $overrides = []): object
{
    $defaults = [
        'id' => 1,
        'status' => \App\Enums\PolicyIssuanceEnum::PENDING_STATUS,
        'completed_step' => null,
        'insurance_provider_id' => 1,
        'model_type' => \App\Models\PersonalQuote::class,
        'model_id' => 1,
        'quote_type' => \App\Enums\QuoteTypes::DEVICE->value,
    ];

    return (object) array_merge($defaults, $overrides);
}

/**
 * Create a mock customer object for NGI tests.
 *
 * @param  array  $overrides  Custom field values
 */
function ngiMockCustomer(array $overrides = []): object
{
    $defaults = [
        'id' => 1,
        'emirates_id_number' => '784-1234-1234567-1',
        'emirates_id_expiry_date' => now()->addYears(2)->format('Y-m-d'),
        'dob' => '1990-01-15',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'mobile_no' => '+971501234567',
        'address' => '123 Test Street, Dubai',
    ];

    return (object) array_merge($defaults, $overrides);
}

/**
 * Create a mock device quote object for NGI tests.
 *
 * @param  array  $overrides  Custom field values
 */
function ngiMockDeviceQuote(array $overrides = []): object
{
    $defaults = [
        'id' => 1,
        'imei' => '123456789012345',
        'device_make' => 'Apple',
        'device_model' => 'iPhone 15 Pro',
        'device_type' => 'smartphone',
        'device_value' => 5000.00,
    ];

    return (object) array_merge($defaults, $overrides);
}

/**
 * Create a mock insured object for NGI tests.
 *
 * @param  array  $overrides  Custom field values
 */
function ngiMockInsured(array $overrides = []): object
{
    $defaults = [
        'id' => 1,
        'id_type' => 'emiratesId',
        'id_number' => '784-1234-1234567-1',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ];

    return (object) array_merge($defaults, $overrides);
}
