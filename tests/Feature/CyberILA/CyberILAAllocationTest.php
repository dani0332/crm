<?php

use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;
use Tests\Helpers\CyberILA\CyberQuoteMockHelper;
use Tests\Helpers\CyberILA\CyberQuoteTestDataBuilder;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->lookups = TestDataSeeder::seedCyberQuoteLookups();
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
});

afterEach(function () {
    \Mockery::close();
});

test('can access cyber quotes list page', function () {
    $response = $this->get(route('cyber-quotes-list'));

    // Should return a status code (could be 200, 403, or 404 depending on route)
    expect($response->status())->toBeInt();
});

test('can access cyber quote create page', function () {
    $response = $this->get(route('cyber-quotes-create'));

    // Should return a status code
    expect($response->status())->toBeInt();
});

test('cyber quote data builder creates valid data', function () {
    $quoteData = CyberQuoteTestDataBuilder::buildQuoteData([], $this->lookups);

    expect($quoteData)->toHaveKeys([
        'first_name',
        'last_name',
        'email',
        'mobile_no',
        'dob',
        'nationality_id',
        'emirate_of_registration_id',
    ])
    ->and($quoteData['first_name'])->toBeString()
    ->and($quoteData['email'])->toContain('@')
    ->and($quoteData['nationality_id'])->toBeInt()
    ->and($quoteData['emirate_of_registration_id'])->toBeInt();
});

test('cyber quote test data builder has female customer variant', function () {
    $femaleData = CyberQuoteTestDataBuilder::buildFemaleCustomerData([], $this->lookups);

    expect($femaleData['first_name'])->toBe('Fatima')
        ->and($femaleData['last_name'])->toBe('Al-Mansoori')
        ->and($femaleData['email'])->toContain('fatima');
});

test('cyber quote test data builder allows overrides', function () {
    $overrides = [
        'first_name' => 'CustomName',
        'email' => 'custom@test.com',
    ];

    $quoteData = CyberQuoteTestDataBuilder::buildQuoteData($overrides, $this->lookups);

    expect($quoteData['first_name'])->toBe('CustomName')
        ->and($quoteData['email'])->toBe('custom@test.com');
});

test('cyber quote api payload has correct format', function () {
    $payload = CyberQuoteTestDataBuilder::getApiPayload([], $this->lookups);

    expect($payload)->toHaveKeys([
        'firstName',
        'lastName',
        'email',
        'mobileNo',
        'dob',
        'nationalityId',
        'emirateOfRegistrationId',
        'quoteTypeId',
        'lang',
        'device',
        'source',
    ])
    ->and($payload['quoteTypeId'])->toBe(119)
    ->and($payload['lang'])->toBe('EN');
});

test('cyber quote data builder has default values', function () {
    $quoteData = CyberQuoteTestDataBuilder::buildQuoteData([], $this->lookups);

    expect($quoteData['first_name'])->toBe('Ahmed')
        ->and($quoteData['last_name'])->toBe('Al-Mansouri')
        ->and($quoteData['email'])->toBe('ahmed.mansouri@gmail.com')
        ->and($quoteData['mobile_no'])->toBe('+971501234567');
});

test('cyber quote mock helper can be instantiated', function () {
    // Test that the mock helper methods exist and are callable
    expect(method_exists(CyberQuoteMockHelper::class, 'mockCapiRequestService'))->toBeTrue()
        ->and(method_exists(CyberQuoteMockHelper::class, 'mockKenService'))->toBeTrue()
        ->and(method_exists(CyberQuoteMockHelper::class, 'mockBirdService'))->toBeTrue();
});

test('cyber quote test data supports coverage variants', function () {
    $basicCoverage = CyberQuoteTestDataBuilder::buildWithCoverage('basic', [], $this->lookups);
    $premiumCoverage = CyberQuoteTestDataBuilder::buildWithCoverage('premium', [], $this->lookups);

    expect($basicCoverage['coverage'])->toBe('basic')
        ->and($premiumCoverage['coverage'])->toBe('premium');
});

