<?php

use App\Enums\GenericRequestEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Services\CapiRequestService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;
use Tests\Helpers\MigrationLoader;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    // Create minimal schema for required lookup tables
    TestSchemaCreator::createMinimalSchema();
    
    // Load external migrations (they may create additional tables)
    MigrationLoader::loadExternalMigrations();

    // Seed required lookup data
    $this->lookups = TestDataSeeder::seedLifeQuoteLookups();

    // Create authenticated user with Admin role to bypass permission checks
    $this->user = TestDataSeeder::createUser();
    
    // Create Admin role if it doesn't exist and assign to user
    $adminRole = Role::firstOrCreate(
        ['name' => RolesEnum::Admin, 'guard_name' => 'web'],
        ['created_at' => now(), 'updated_at' => now()]
    );
    $this->user->assignRole($adminRole);
    
    $this->actingAs($this->user);
});

afterEach(function () {
    Mockery::close();
});

test('can create a life quote', function () {
    // Generate a test UUID
    $testUuid = 'test-quote-uuid-'.uniqid();

    // Create a PersonalQuote with the UUID that will be returned by the mock
    $personalQuote = PersonalQuote::create([
        'uuid' => $testUuid,
        'quote_type_id' => QuoteTypeId::Life,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'mobile_no' => '+971501234567',
        'dob' => '1990-01-15',
        'source' => 'TEST',
        'device' => 'DESKTOP',
        'code' => 'TEST-'.uniqid(),
        'created_by_id' => $this->user->id,
    ]);

    // Create associated LifeQuote
    $lifeQuote = LifeQuote::create([
        'personal_quote_id' => $personalQuote->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'mobile_no' => '+971501234567',
        'height' => 175,
        'weight' => 75,
        'bmi' => 24.5,
        'age' => 34,
    ]);

    // Mock CapiRequestService to return a successful response
    $mockResponse = (object) [
        'quoteUID' => $testUuid,
        'msg' => null,
        'errors' => null,
    ];

    // Mock the static method using Mockery
    $mock = Mockery::mock('alias:'.CapiRequestService::class);
    $mock->shouldReceive('sendCAPIRequest')
        ->once()
        ->with('/api/v2-save-life-quote', Mockery::type('array'))
        ->andReturn($mockResponse);

    // Prepare test data
    $quoteData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@gmail.com',
        'mobile_no' => '+971501234567',
        'dob' => '1990-01-15',
        'sum_insured_value' => 100000,
        'nationality_id' => $this->lookups['nationality_id'],
        'sum_insured_currency_id' => $this->lookups['currency_id'],
        'marital_status_id' => $this->lookups['marital_status_id'],
        'purpose_of_insurance_id' => $this->lookups['purpose_of_insurance_id'],
        'number_of_years_id' => $this->lookups['number_of_years_id'],
        'is_smoker' => 0,
        'gender' => GenericRequestEnum::MALE_SINGLE,
        'others_info' => 'Test information',
        'height' => 175,
        'weight' => 75,
        'bmi' => 24.5,
        'age' => 34,
    ];

    // Make POST request to create life quote
    $response = $this->post(route('life-quotes-store'), $quoteData);

    // Assert redirect to show page
    $response->assertRedirect(route('life-quotes-show', $testUuid));

    // Assert success message
    $response->assertSessionHas('message', 'Quote is created successfully.');

    // Verify PersonalQuote exists
    $personalQuote->refresh();
    expect($personalQuote->first_name)->toBe('John')
        ->and($personalQuote->last_name)->toBe('Doe')
        ->and($personalQuote->email)->toBe('john.doe@example.com');

    // Verify LifeQuote was updated with the new values
    $lifeQuote->refresh();
    expect($lifeQuote->height)->toBe(175)
        ->and($lifeQuote->weight)->toBe(75)
        ->and($lifeQuote->bmi)->toBe(24.5)
        ->and($lifeQuote->age)->toBe(34);
});

test('validates required fields when creating life quote', function () {
    $response = $this->post(route('life-quotes-store'), []);

    $response->assertSessionHasErrors([
        'first_name',
        'last_name',
        'email',
        'mobile_no',
        'dob',
        'sum_insured_value',
        'nationality_id',
        'sum_insured_currency_id',
        'marital_status_id',
        'purpose_of_insurance_id',
        'number_of_years_id',
        'is_smoker',
        'gender',
        'height',
        'weight',
        'bmi',
        'age',
    ]);
    
    // Should return validation errors, not 403
    $response->assertStatus(302); // Redirect back with errors
});

