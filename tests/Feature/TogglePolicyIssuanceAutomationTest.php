<?php

use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\CarQuoteTestDataBuilder;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
    
    // Use DB facade to create records directly in SQLite
    $db = DB::connection('sqlite');
    
    // Create insurance provider
    $this->insuranceProviderId = $db->table('insurance_provider')->insertGetId([
        'code' => InsuranceProvidersEnum::RSA,
        'text' => 'RSA Insurance',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    // Create car plan
    $this->carPlanId = $db->table('car_plan')->insertGetId([
        'insurance_provider_id' => $this->insuranceProviderId,
        'plan_name' => 'Test Car Plan',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

afterEach(function () {
    Mockery::close();
});

test('can enable policy issuance automation for car quote', function () {
    $db = DB::connection('sqlite');
    
    // Create a car quote
    $quoteUuid = 'test-car-quote-'.uniqid();
    $db->table('car_quote_request')->insert([
        'uuid' => $quoteUuid,
        'code' => 'CQ-TEST-'.rand(100000, 999999),
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@test.com',
        'mobile_no' => '+971501234567',
        'plan_id' => $this->carPlanId,
        'policy_issuance_automation_enabled' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Mock the PolicyIssuanceService
    $mockService = Mockery::mock(PolicyIssuanceService::class);
    $mockService->shouldReceive('togglePolicyIssuanceAutomation')
        ->once()
        ->andReturn([
            'success' => true,
            'message' => 'Policy issuance automation enabled successfully',
            'data' => [
                'policy_issuance_automation_enabled' => true,
            ],
            'status_code' => 200,
        ]);

    $this->app->instance(PolicyIssuanceService::class, $mockService);

    // Make the request
    $response = $this->postJson(route('toggle-policy-issuance-automation'), [
        'quote_uuid' => $quoteUuid,
        'quote_type_id' => QuoteTypeId::Car,
        'enabled' => true,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Policy issuance automation enabled successfully',
        ]);
});

test('can disable policy issuance automation for car quote', function () {
    $db = DB::connection('sqlite');
    
    // Create a car quote with automation enabled
    $quoteUuid = 'test-car-quote-'.uniqid();
    $db->table('car_quote_request')->insert([
        'uuid' => $quoteUuid,
        'code' => 'CQ-TEST-'.rand(100000, 999999),
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@test.com',
        'mobile_no' => '+971501234567',
        'plan_id' => $this->carPlanId,
        'policy_issuance_automation_enabled' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Mock the PolicyIssuanceService
    $mockService = Mockery::mock(PolicyIssuanceService::class);
    $mockService->shouldReceive('togglePolicyIssuanceAutomation')
        ->once()
        ->andReturn([
            'success' => true,
            'message' => 'Policy issuance automation disabled successfully',
            'data' => [
                'policy_issuance_automation_enabled' => false,
            ],
            'status_code' => 200,
        ]);

    $this->app->instance(PolicyIssuanceService::class, $mockService);

    // Make the request
    $response = $this->postJson(route('toggle-policy-issuance-automation'), [
        'quote_uuid' => $quoteUuid,
        'quote_type_id' => QuoteTypeId::Car,
        'enabled' => false,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Policy issuance automation disabled successfully',
        ]);
});

test('returns 404 when quote not found', function () {
    // Mock the PolicyIssuanceService to return quote not found
    $mockService = Mockery::mock(PolicyIssuanceService::class);
    $mockService->shouldReceive('togglePolicyIssuanceAutomation')
        ->once()
        ->andReturn([
            'success' => false,
            'message' => 'Quote not found',
            'status_code' => 404,
        ]);

    $this->app->instance(PolicyIssuanceService::class, $mockService);

    $response = $this->postJson(route('toggle-policy-issuance-automation'), [
        'quote_uuid' => 'non-existent-uuid',
        'quote_type_id' => QuoteTypeId::Car,
        'enabled' => true,
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Quote not found',
        ]);
});

test('validates required fields', function () {
    $response = $this->postJson(route('toggle-policy-issuance-automation'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'quote_uuid',
            'quote_type_id',
            'enabled',
        ]);
});

test('validates quote_type_id must be Car', function () {
    $response = $this->postJson(route('toggle-policy-issuance-automation'), [
        'quote_uuid' => 'test-uuid',
        'quote_type_id' => QuoteTypeId::Life, // Not Car
        'enabled' => true,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['quote_type_id']);
});

test('validates enabled must be boolean', function () {
    $db = DB::connection('sqlite');
    
    $quoteUuid = 'test-car-quote-'.uniqid();
    $db->table('car_quote_request')->insert([
        'uuid' => $quoteUuid,
        'code' => 'CQ-TEST-'.rand(100000, 999999),
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@test.com',
        'mobile_no' => '+971501234567',
        'plan_id' => $this->carPlanId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->postJson(route('toggle-policy-issuance-automation'), [
        'quote_uuid' => $quoteUuid,
        'quote_type_id' => QuoteTypeId::Car,
        'enabled' => 'invalid', // Not boolean
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['enabled']);
});

test('returns 400 when insurance provider not found', function () {
    $db = DB::connection('sqlite');
    
    // Create a car quote without insurance provider
    $quoteUuid = 'test-car-quote-'.uniqid();
    $db->table('car_quote_request')->insert([
        'uuid' => $quoteUuid,
        'code' => 'CQ-TEST-'.rand(100000, 999999),
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@test.com',
        'mobile_no' => '+971501234567',
        'plan_id' => null, // No plan
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Mock the PolicyIssuanceService to return insurance provider not found
    $mockService = Mockery::mock(PolicyIssuanceService::class);
    $mockService->shouldReceive('togglePolicyIssuanceAutomation')
        ->once()
        ->andReturn([
            'success' => false,
            'message' => 'Insurance provider not found',
            'status_code' => 400,
        ]);

    $this->app->instance(PolicyIssuanceService::class, $mockService);

    $response = $this->postJson(route('toggle-policy-issuance-automation'), [
        'quote_uuid' => $quoteUuid,
        'quote_type_id' => QuoteTypeId::Car,
        'enabled' => true,
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Insurance provider not found',
        ]);
});

test('returns 400 when policy automation is not enabled for insurer', function () {
    $db = DB::connection('sqlite');
    
    $quoteUuid = 'test-car-quote-'.uniqid();
    $db->table('car_quote_request')->insert([
        'uuid' => $quoteUuid,
        'code' => 'CQ-TEST-'.rand(100000, 999999),
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@test.com',
        'mobile_no' => '+971501234567',
        'plan_id' => $this->carPlanId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Mock the PolicyIssuanceService to return automation not enabled
    $mockService = Mockery::mock(PolicyIssuanceService::class);
    $mockService->shouldReceive('togglePolicyIssuanceAutomation')
        ->once()
        ->andReturn([
            'success' => false,
            'message' => 'Policy automation is not enabled for this insurer',
            'status_code' => 400,
        ]);

    $this->app->instance(PolicyIssuanceService::class, $mockService);

    $response = $this->postJson(route('toggle-policy-issuance-automation'), [
        'quote_uuid' => $quoteUuid,
        'quote_type_id' => QuoteTypeId::Car,
        'enabled' => true,
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Policy automation is not enabled for this insurer',
        ]);
});

test('handles exceptions and returns 500', function () {
    $db = DB::connection('sqlite');
    
    $quoteUuid = 'test-car-quote-'.uniqid();
    $db->table('car_quote_request')->insert([
        'uuid' => $quoteUuid,
        'code' => 'CQ-TEST-'.rand(100000, 999999),
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@test.com',
        'mobile_no' => '+971501234567',
        'plan_id' => $this->carPlanId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Mock the PolicyIssuanceService to throw exception
    $mockService = Mockery::mock(PolicyIssuanceService::class);
    $mockService->shouldReceive('togglePolicyIssuanceAutomation')
        ->once()
        ->andThrow(new \Exception('Something went wrong'));

    $this->app->instance(PolicyIssuanceService::class, $mockService);

    $response = $this->postJson(route('toggle-policy-issuance-automation'), [
        'quote_uuid' => $quoteUuid,
        'quote_type_id' => QuoteTypeId::Car,
        'enabled' => true,
    ]);

    $response->assertStatus(500)
        ->assertJson([
            'success' => false,
            'message' => 'Unable to toggle policy issuance automation, Please try again later.',
        ]);
});

test('requires authentication', function () {
    // Logout the user
    auth()->logout();

    $response = $this->postJson(route('toggle-policy-issuance-automation'), [
        'quote_uuid' => 'test-uuid',
        'quote_type_id' => QuoteTypeId::Car,
        'enabled' => true,
    ]);

    $response->assertStatus(401);
});

