<?php

declare(strict_types=1);

use App\Enums\GenericRequestEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\V2\PersonalQuoteController;
use App\Http\Requests\ChangePrimaryContactRequest;
use App\Models\PersonalQuote;
use App\Services\CustomerService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\TestSchemaCreator;

if (! defined('QUOTE_TYPE')) {
    define('QUOTE_TYPE', QuoteTypes::CAR);
}

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    Queue::fake();
});

afterEach(function () {
    Mockery::close();
});

test('changePrimaryContact calls service makeAdditionalContactPrimary with correct parameters when keep_existing_primary_email is provided', function () {
    // Setup: Create a PersonalQuote using model (without events for speed)
    $personalQuote = PersonalQuote::withoutEvents(fn () => PersonalQuote::create([
        'code' => 'CAR-TEST-123',
        'uuid' => 'test-uuid-123',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'mobile_no' => '+971501234567',
        'customer_id' => 1,
        'quote_type_id' => QUOTE_TYPE->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ]));
    $personalQuoteId = $personalQuote->id;

    // Mock CustomerService
    $mockCustomerService = Mockery::mock(CustomerService::class);
    $mockCustomerService->shouldReceive('makeAdditionalContactPrimary')
        ->once()
        ->with(
            Mockery::on(function ($quote) use ($personalQuoteId) {
                return $quote->id === $personalQuoteId;
            }),
            GenericRequestEnum::EMAIL,
            'newemail@example.com',
            true
        );

    // Bind mock to service container
    App::instance(CustomerService::class, $mockCustomerService);

    // Create mock request
    $mockRequest = Mockery::mock(ChangePrimaryContactRequest::class);
    $mockRequest->keep_existing_primary_email = 1;
    $mockRequest->key = GenericRequestEnum::EMAIL;
    $mockRequest->value = 'newemail@example.com';

    // Create controller instance
    $controller = new PersonalQuoteController;

    // Action: Call the method
    $result = $controller->changePrimaryContact($personalQuoteId, $mockRequest);

    // Assert: Should return redirect response
    expect($result)->toBeInstanceOf(RedirectResponse::class);
});

test('changePrimaryContact uses default keepExistingPrimaryEmail value of 1 when not provided', function () {
    // Setup: Create a PersonalQuote using model (without events for speed)
    $personalQuote = PersonalQuote::withoutEvents(fn () => PersonalQuote::create([
        'code' => 'CAR-TEST-456',
        'uuid' => 'test-uuid-456',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'mobile_no' => '+971501234567',
        'customer_id' => 1,
        'quote_type_id' => QUOTE_TYPE->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ]));
    $personalQuoteId = $personalQuote->id;

    // Mock CustomerService - should be called with true (default value)
    $mockCustomerService = Mockery::mock(CustomerService::class);
    $mockCustomerService->shouldReceive('makeAdditionalContactPrimary')
        ->once()
        ->with(
            Mockery::on(function ($quote) use ($personalQuoteId) {
                return $quote->id === $personalQuoteId;
            }),
            GenericRequestEnum::EMAIL,
            'newemail@example.com',
            true // Default value when keep_existing_primary_email is not set
        );

    // Bind mock to service container
    App::instance(CustomerService::class, $mockCustomerService);

    // Create mock request without keep_existing_primary_email
    $mockRequest = Mockery::mock(ChangePrimaryContactRequest::class);
    $mockRequest->keep_existing_primary_email = null; // Not set
    $mockRequest->key = GenericRequestEnum::EMAIL;
    $mockRequest->value = 'newemail@example.com';

    // Create controller instance
    $controller = new PersonalQuoteController;

    // Action: Call the method
    $result = $controller->changePrimaryContact($personalQuoteId, $mockRequest);

    // Assert: Should return redirect response
    expect($result)->toBeInstanceOf(RedirectResponse::class);
});

test('changePrimaryContact handles mobile_no key correctly', function () {
    // Setup: Create a PersonalQuote using model (without events for speed)
    $personalQuote = PersonalQuote::withoutEvents(fn () => PersonalQuote::create([
        'code' => 'CAR-TEST-789',
        'uuid' => 'test-uuid-789',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'mobile_no' => '+971501234567',
        'customer_id' => 1,
        'quote_type_id' => QUOTE_TYPE->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ]));
    $personalQuoteId = $personalQuote->id;

    // Mock CustomerService
    $mockCustomerService = Mockery::mock(CustomerService::class);
    $mockCustomerService->shouldReceive('makeAdditionalContactPrimary')
        ->once()
        ->with(
            Mockery::on(function ($quote) use ($personalQuoteId) {
                return $quote->id === $personalQuoteId;
            }),
            GenericRequestEnum::MOBILE_NO,
            '+971509876543',
            false
        );

    // Bind mock to service container
    App::instance(CustomerService::class, $mockCustomerService);

    // Create mock request
    $mockRequest = Mockery::mock(ChangePrimaryContactRequest::class);
    $mockRequest->keep_existing_primary_email = 0;
    $mockRequest->key = GenericRequestEnum::MOBILE_NO;
    $mockRequest->value = '+971509876543';

    // Create controller instance
    $controller = new PersonalQuoteController;

    // Action: Call the method
    $result = $controller->changePrimaryContact($personalQuoteId, $mockRequest);

    // Assert: Should return redirect response
    expect($result)->toBeInstanceOf(RedirectResponse::class);
});

test('changePrimaryContact throws ModelNotFoundException when quote not found', function () {
    // Create mock request
    $mockRequest = Mockery::mock(ChangePrimaryContactRequest::class);
    $mockRequest->key = GenericRequestEnum::EMAIL;
    $mockRequest->value = 'test@example.com';

    // Create controller instance
    $controller = new PersonalQuoteController;

    // Action & Assert: Should throw ModelNotFoundException
    expect(fn () => $controller->changePrimaryContact(99999, $mockRequest))
        ->toThrow(ModelNotFoundException::class);
});

test('changePrimaryContact converts keep_existing_primary_email to boolean correctly', function () {
    // Setup: Create a PersonalQuote using model (without events for speed)
    $personalQuote = PersonalQuote::withoutEvents(fn () => PersonalQuote::create([
        'code' => 'CAR-TEST-ABC',
        'uuid' => 'test-uuid-abc',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'mobile_no' => '+971501234567',
        'customer_id' => 1,
        'quote_type_id' => QUOTE_TYPE->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ]));
    $personalQuoteId = $personalQuote->id;

    // Mock CustomerService - should receive boolean true even if request has string '1'
    $mockCustomerService = Mockery::mock(CustomerService::class);
    $mockCustomerService->shouldReceive('makeAdditionalContactPrimary')
        ->once()
        ->with(
            Mockery::on(function ($quote) use ($personalQuoteId) {
                return $quote->id === $personalQuoteId;
            }),
            GenericRequestEnum::EMAIL,
            'newemail@example.com',
            Mockery::on(function ($arg) {
                return $arg === true; // Should be boolean, not string
            })
        );

    // Bind mock to service container
    App::instance(CustomerService::class, $mockCustomerService);

    // Create mock request with string value
    $mockRequest = Mockery::mock(ChangePrimaryContactRequest::class);
    $mockRequest->keep_existing_primary_email = '1'; // String value
    $mockRequest->key = GenericRequestEnum::EMAIL;
    $mockRequest->value = 'newemail@example.com';

    // Create controller instance
    $controller = new PersonalQuoteController;

    // Action: Call the method
    $result = $controller->changePrimaryContact($personalQuoteId, $mockRequest);

    // Assert: Should return redirect response
    expect($result)->toBeInstanceOf(RedirectResponse::class);
});

test('changePrimaryContact allows service exceptions to bubble up', function () {
    // Setup: Create a PersonalQuote using model (without events for speed)
    $personalQuote = PersonalQuote::withoutEvents(fn () => PersonalQuote::create([
        'code' => 'CAR-TEST-EXCEPTION',
        'uuid' => 'test-uuid-exception',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'mobile_no' => '+971501234567',
        'customer_id' => 1,
        'quote_type_id' => QUOTE_TYPE->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ]));
    $personalQuoteId = $personalQuote->id;

    // Mock CustomerService to throw an exception
    $mockCustomerService = Mockery::mock(CustomerService::class);
    $mockCustomerService->shouldReceive('makeAdditionalContactPrimary')
        ->once()
        ->andThrow(new RuntimeException('Service error occurred'));

    // Bind mock to service container
    App::instance(CustomerService::class, $mockCustomerService);

    // Create mock request
    $mockRequest = Mockery::mock(ChangePrimaryContactRequest::class);
    $mockRequest->keep_existing_primary_email = 1;
    $mockRequest->key = GenericRequestEnum::EMAIL;
    $mockRequest->value = 'newemail@example.com';

    // Create controller instance
    $controller = new PersonalQuoteController;

    // Action & Assert: Should allow exception to bubble up
    expect(fn () => $controller->changePrimaryContact($personalQuoteId, $mockRequest))
        ->toThrow(RuntimeException::class, 'Service error occurred');
});

test('changePrimaryContact handles invalid key value gracefully', function () {
    // Setup: Create a PersonalQuote using model (without events for speed)
    $personalQuote = PersonalQuote::withoutEvents(fn () => PersonalQuote::create([
        'code' => 'CAR-TEST-INVALID-KEY',
        'uuid' => 'test-uuid-invalid-key',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'mobile_no' => '+971501234567',
        'customer_id' => 1,
        'quote_type_id' => QUOTE_TYPE->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ]));
    $personalQuoteId = $personalQuote->id;

    // Note: In unit tests, we directly call the controller method, so FormRequest
    // validation (which happens at middleware level) won't run. However, we can
    // test that the controller passes invalid data to the service, which may
    // handle it or throw an exception.

    // Create mock request with invalid key (not EMAIL or MOBILE_NO)
    $mockRequest = Mockery::mock(ChangePrimaryContactRequest::class);
    $mockRequest->key = 'invalid_key'; // Invalid - should be EMAIL or MOBILE_NO
    $mockRequest->value = 'newemail@example.com';
    $mockRequest->keep_existing_primary_email = 1;

    // Mock CustomerService - will receive invalid key
    // The service may handle this or throw an exception depending on implementation
    $mockCustomerService = Mockery::mock(CustomerService::class);
    $mockCustomerService->shouldReceive('makeAdditionalContactPrimary')
        ->once()
        ->with(
            Mockery::any(),
            'invalid_key',
            'newemail@example.com',
            true
        );

    // Bind mock to service container
    App::instance(CustomerService::class, $mockCustomerService);

    // Create controller instance
    $controller = new PersonalQuoteController;

    // Action: Call the method - controller doesn't validate, it passes data to service
    // Note: In real Laravel flow, FormRequest validation would prevent this
    // from reaching the controller. Validation is tested in feature tests.
    $result = $controller->changePrimaryContact($personalQuoteId, $mockRequest);

    // Assert: Controller still returns redirect (validation happens at request level)
    expect($result)->toBeInstanceOf(RedirectResponse::class);
});
