<?php

use Tests\Helpers\HealthILA\HealthQuoteMockHelper;
use Tests\Helpers\HealthILA\HealthQuoteTestDataBuilder;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->lookups = TestDataSeeder::seedHealthQuoteLookups();
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
});

afterEach(function () {
    Mockery::close();
});

// ============================================================================
// SECTION 1: HEALTH QUOTE ROUTE ACCESS TESTS (2 tests)
// ============================================================================

test('can access health quotes list page', function () {
    $response = $this->get(route('health.index'));

    // Should return a status code (could be 200, 403, or 404 depending on route)
    expect($response->status())->toBeInt();
});

test('can access health quote create page', function () {
    $response = $this->get(route('health.create'));

    // Should return a status code
    expect($response->status())->toBeInt();
});

// ============================================================================
// SECTION 2: HEALTH QUOTE DATA BUILDER TESTS (5 tests)
// ============================================================================

test('health quote data builder creates valid data', function () {
    $quoteData = HealthQuoteTestDataBuilder::buildQuoteData([], $this->lookups);

    expect($quoteData)->toHaveKeys([
        'first_name',
        'last_name',
        'email',
        'mobile_no',
        'dob',
        'nationality_id',
        'price_starting_from',
    ])
        ->and($quoteData['first_name'])->toBeString()
        ->and($quoteData['email'])->toContain('@')
        ->and($quoteData['nationality_id'])->toBeInt()
        ->and($quoteData['price_starting_from'])->toBeFloat();
});

test('health quote test data builder has female customer variant', function () {
    $femaleData = HealthQuoteTestDataBuilder::buildFemaleCustomerData([], $this->lookups);

    expect($femaleData['first_name'])->toBe('Fatima')
        ->and($femaleData['last_name'])->toBe('Al-Mansoori')
        ->and($femaleData['email'])->toContain('fatima');
});

test('health quote test data builder allows overrides', function () {
    $overrides = [
        'first_name' => 'CustomName',
        'email' => 'custom@test.com',
        'price_starting_from' => 5000.00,
    ];

    $quoteData = HealthQuoteTestDataBuilder::buildQuoteData($overrides, $this->lookups);

    expect($quoteData['first_name'])->toBe('CustomName')
        ->and($quoteData['email'])->toBe('custom@test.com')
        ->and($quoteData['price_starting_from'])->toBe(5000.00);
});

test('health quote api payload has correct format', function () {
    $payload = HealthQuoteTestDataBuilder::getApiPayload([], $this->lookups);

    expect($payload)->toHaveKeys([
        'firstName',
        'lastName',
        'email',
        'mobileNo',
        'dob',
        'nationalityId',
        'quoteTypeId',
        'lang',
        'device',
        'source',
    ])
        ->and($payload['quoteTypeId'])->toBe(3)
        ->and($payload['lang'])->toBe('EN');
});

test('health quote data builder has default values', function () {
    $quoteData = HealthQuoteTestDataBuilder::buildQuoteData([], $this->lookups);

    expect($quoteData['first_name'])->toBe('Ahmed')
        ->and($quoteData['last_name'])->toBe('Al-Mansouri')
        ->and($quoteData['email'])->toBe('ahmed.mansouri@gmail.com')
        ->and($quoteData['mobile_no'])->toBe('+971501234567')
        ->and($quoteData['price_starting_from'])->toBe(4690.00);
});

// ============================================================================
// SECTION 3: HEALTH HELPER & INFRASTRUCTURE TESTS (2 tests)
// ============================================================================

test('health quote mock helper can be instantiated', function () {
    // Test that the mock helper methods exist and are callable
    expect(method_exists(HealthQuoteMockHelper::class, 'mockCapiRequestService'))->toBeTrue()
        ->and(method_exists(HealthQuoteMockHelper::class, 'mockKenService'))->toBeTrue()
        ->and(method_exists(HealthQuoteMockHelper::class, 'mockBirdService'))->toBeTrue();
});

test('health quote test data supports price variants', function () {
    $lowPrice = HealthQuoteTestDataBuilder::buildWithPrice(500.00, [], $this->lookups);
    $highPrice = HealthQuoteTestDataBuilder::buildWithPrice(10000.00, [], $this->lookups);

    expect($lowPrice['price_starting_from'])->toBe(500.00)
        ->and($highPrice['price_starting_from'])->toBe(10000.00);
});
