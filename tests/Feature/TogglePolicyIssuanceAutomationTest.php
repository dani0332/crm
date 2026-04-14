<?php

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarPlan;
use App\Models\CarQuote;
use App\Models\InsuranceProvider;
use App\Services\OCR\OCRService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\TestDataSeeder;

beforeEach(function () {
    $this->user = TestDataSeeder::createAdminUser();

    // Grant the required permission to the user
    $permission = Permission::firstOrCreate(
        ['name' => PermissionsEnum::CAR_LEGACY_KYC_SKIP_INSURER_API, 'guard_name' => 'web'],
        ['created_at' => now(), 'updated_at' => now()]
    );
    $this->user->givePermissionTo($permission);

    // Clear permission cache to ensure permissions are available immediately
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->user->refresh();

    // Mock OCRService to avoid dependency resolution issues in HandleInertiaRequests middleware
    $ocrServiceMock = Mockery::mock(OCRService::class);
    $ocrServiceMock->shouldReceive('getEligibleProviders')->andReturn([]);
    $this->app->instance(OCRService::class, $ocrServiceMock);

    $this->actingAs($this->user);

    // Use Laravel factories to create test data
    $this->insuranceProvider = InsuranceProvider::factory()->rsa()->createOneQuietly();
    $this->carPlan = CarPlan::factory()->forInsuranceProvider($this->insuranceProvider->id)->createOneQuietly();
});

afterEach(function () {
    Mockery::close();
});

test('can enable policy issuance automation for car quote', function () {
    // Create a car quote using Laravel factory
    $carQuote = CarQuote::factory()
        ->withAutomationDisabled()
        ->forCarPlan($this->carPlan->id)
        ->createOneQuietly();

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
        'quote_uuid' => $carQuote->uuid,
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
    // Create a car quote with automation enabled using Laravel factory
    $carQuote = CarQuote::factory()
        ->withAutomationEnabled()
        ->forCarPlan($this->carPlan->id)
        ->createOneQuietly();

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
        'quote_uuid' => $carQuote->uuid,
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
    // Create a car quote using Laravel factory
    $carQuote = CarQuote::factory()
        ->forCarPlan($this->carPlan->id)
        ->createOneQuietly();

    $response = $this->postJson(route('toggle-policy-issuance-automation'), [
        'quote_uuid' => $carQuote->uuid,
        'quote_type_id' => QuoteTypeId::Car,
        'enabled' => 'invalid', // Not boolean
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['enabled']);
});

test('returns 400 when insurance provider not found', function () {
    // Create a car quote without insurance provider using Laravel factory
    $carQuote = CarQuote::factory()
        ->withoutCarPlan()
        ->createOneQuietly();

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
        'quote_uuid' => $carQuote->uuid,
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
    // Create a car quote using Laravel factory
    $carQuote = CarQuote::factory()
        ->forCarPlan($this->carPlan->id)
        ->createOneQuietly();

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
        'quote_uuid' => $carQuote->uuid,
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
    // Create a car quote using Laravel factory
    $carQuote = CarQuote::factory()
        ->forCarPlan($this->carPlan->id)
        ->createOneQuietly();

    // Mock the PolicyIssuanceService to throw exception
    $mockService = Mockery::mock(PolicyIssuanceService::class);
    $mockService->shouldReceive('togglePolicyIssuanceAutomation')
        ->once()
        ->andThrow(new Exception('Something went wrong'));

    $this->app->instance(PolicyIssuanceService::class, $mockService);

    $response = $this->postJson(route('toggle-policy-issuance-automation'), [
        'quote_uuid' => $carQuote->uuid,
        'quote_type_id' => QuoteTypeId::Car,
        'enabled' => true,
    ]);

    $response->assertStatus(500);

    // Check that we get an error response (message may vary based on exception handling)
    $json = $response->json();
    expect($json)->toHaveKey('message')
        ->and(in_array($json['message'], [
            'Unable to toggle policy issuance automation, Please try again later.',
            'Server Error',
        ]))->toBeTrue();
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
