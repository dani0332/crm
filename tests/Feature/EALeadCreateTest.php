<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Http\Middleware\PreventRequestForgery;
use App\Jobs\SendEALeadSubmittedEmailJob;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->withoutMiddleware(PreventRequestForgery::class);

    DB::connection('sqlite')->table('quote_type')->insertOrIgnore([
        ['id' => QuoteTypeId::Car, 'code' => 'Car', 'short_code' => 'CAR', 'text' => 'Car Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => QuoteTypeId::Health, 'code' => 'Health', 'short_code' => 'HLT', 'text' => 'Health Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => QuoteTypeId::Travel, 'code' => 'Travel', 'short_code' => 'TRV', 'text' => 'Travel Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 20, 'code' => 'Cyber', 'short_code' => 'CYB', 'text' => 'Cyber Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
    ]);
});

it('returns 403 when user has no EA role or permission', function () {
    $user = TestDataSeeder::createUser(['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $response = $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'referral',
        'quote_type_id' => QuoteTypeId::Car,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'mobile_no' => '0501234567',
    ]);

    $response->assertStatus(403);
});

it('creates a referral EA lead with correct source and lead_generator_id', function () {
    Queue::fake();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $response = $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'referral',
        'quote_type_id' => QuoteTypeId::Car,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
        'mobile_no' => '0501234568',
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    $lead = CarQuote::where('email', 'jane@example.com')->first();
    expect($lead)->not->toBeNull();
    expect($lead->source)->toBe(LeadSourceEnum::EA_IMCRM);
    expect($lead->ea_model)->toBe('referral');
    expect($lead->lead_generator_id)->toBe($user->id);
    expect($lead->advisor_id)->toBeNull();

    Queue::assertPushed(SendEALeadSubmittedEmailJob::class);
});

it('creates a collaborate lead with advisor_id set to creating user', function () {
    Queue::fake();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::EAReferral, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();

    $this->actingAs($user);

    $response = $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => 20,
        'first_name' => 'Bob',
        'last_name' => 'Builder',
        'email' => 'bob@example.com',
        'mobile_no' => '0501234569',
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    $lead = PersonalQuote::where('email', 'bob@example.com')->first();
    expect($lead)->not->toBeNull();
    expect($lead->ea_model)->toBe('collaborate');
    expect($lead->advisor_id)->toBe($user->id);
});

it('blocks collaborate model for car LOB', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::EAReferral, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();

    $this->actingAs($user);

    $response = $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::Car,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'mobile_no' => '0501234570',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrorFor('quote_type_id');
});

it('blocks collaborate model for health LOB', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::EAReferral, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();

    $this->actingAs($user);

    $response = $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::Health,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'mobile_no' => '0501234571',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrorFor('quote_type_id');
});

it('forces referral model for EA manager role even if collaborate submitted', function () {
    Queue::fake();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::EAManager, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();

    $this->actingAs($user);

    $response = $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => 20,
        'first_name' => 'Manager',
        'last_name' => 'Lead',
        'email' => 'manager.lead@example.com',
        'mobile_no' => '0501234572',
    ]);

    $response->assertOk();

    $lead = PersonalQuote::where('email', 'manager.lead@example.com')->first();
    expect($lead->ea_model)->toBe('referral');
});

it('returns 422 with duplicate info when lead already exists within 60 days', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-test-001',
        'quote_type_id' => 20,
        'email' => 'dup@example.com',
        'mobile_no' => '0509999999',
        'source' => LeadSourceEnum::EA_IMCRM,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'first_name' => 'Dup',
        'last_name' => 'Test',
        'advisor_id' => $user->id,
    ]);

    $response = $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'referral',
        'quote_type_id' => 20,
        'first_name' => 'Dup',
        'last_name' => 'Test',
        'email' => 'dup@example.com',
        'mobile_no' => '0509999999',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('duplicate', true);
});

it('blocks creation when active renewal-upload lead exists', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    CarQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CAR-renewal-001',
        'email' => 'renewal@example.com',
        'mobile_no' => '0508888888',
        'source' => 'Renewal_upload',
        'policy_expiry_date' => now()->addDays(30)->toDateString(),
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'first_name' => 'Renew',
        'last_name' => 'Test',
    ]);

    $response = $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'referral',
        'quote_type_id' => QuoteTypeId::Car,
        'first_name' => 'Renew',
        'last_name' => 'Test',
        'email' => 'renewal@example.com',
        'mobile_no' => '0508888888',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('duplicate', true);
});
