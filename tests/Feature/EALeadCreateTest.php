<?php

declare(strict_types=1);

use App\Enums\EaModelEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Http\Middleware\PreventRequestForgery;
use App\Http\Requests\EALeadCreateRequest;
use App\Jobs\SendEALeadSubmittedEmailJob;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Services\EALeadCapiService;
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
        ['id' => QuoteTypeId::Life, 'code' => 'Life', 'short_code' => 'LIF', 'text' => 'Life Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => QuoteTypeId::GroupMedical, 'code' => 'GroupMedical', 'short_code' => 'GMD', 'text' => 'Group Medical', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 20, 'code' => 'Cyber', 'short_code' => 'CYB', 'text' => 'Cyber Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
    ]);

    app()->bind(EALeadCapiService::class, function () {
        return new class extends EALeadCapiService
        {
            public function createLead(EALeadCreateRequest $request, int $quoteTypeId, bool $isCollaborate): mixed
            {
                $uuid = Str::uuid()->toString();
                $codePrefix = match ($quoteTypeId) {
                    QuoteTypeId::Car => 'CAR',
                    QuoteTypeId::Health => 'HEA',
                    QuoteTypeId::Life => 'LIF',
                    QuoteTypeId::Corpline, QuoteTypeId::GroupMedical => 'BUS',
                    default => 'CYB',
                };

                $data = [
                    'uuid' => $uuid,
                    'code' => $codePrefix.'-'.$uuid,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'mobile_no' => $request->mobile_no,
                    'source' => LeadSourceEnum::EA_IMCRM,
                    'ea_model' => $request->ea_model,
                    'lead_generator_id' => auth()->id(),
                    'quote_status_id' => QuoteStatusEnum::NewLead,
                    'created_by_id' => auth()->id(),
                    'advisor_id' => $isCollaborate ? auth()->id() : null,
                ];

                if ($quoteTypeId === QuoteTypeId::Car) {
                    CarQuote::create($data);
                } elseif ($quoteTypeId === QuoteTypeId::Health) {
                    HealthQuote::create(array_merge($data, [
                        'health_plan_type_id' => $request->health_plan_type_id,
                    ]));
                } elseif ($quoteTypeId === QuoteTypeId::Life) {
                    LifeQuote::create(array_merge($data, [
                        'quote_type_id' => $quoteTypeId,
                    ]));
                } elseif (in_array($quoteTypeId, [QuoteTypeId::Corpline, QuoteTypeId::GroupMedical], true)) {
                    BusinessQuote::create(array_merge($data, [
                        'business_type_of_insurance_id' => $request->business_type_of_insurance_id,
                    ]));
                } else {
                    PersonalQuote::create(array_merge($data, [
                        'quote_type_id' => $quoteTypeId,
                        'business_type_of_insurance_id' => $request->business_type_of_insurance_id,
                    ]));
                }

                return (object) ['quoteUID' => $uuid];
            }
        };
    });
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
    expect($lead->ea_model)->toBe(EaModelEnum::Referral);
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
    expect($lead->ea_model)->toBe(EaModelEnum::Collaborate);
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
    expect($lead->ea_model)->toBe(EaModelEnum::Referral);
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

it('blocks collaborate model for travel LOB', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::EAReferral, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();

    $this->actingAs($user);

    $response = $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::Travel,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test.travel@example.com',
        'mobile_no' => '0501234573',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrorFor('quote_type_id');
});

it('blocks collaborate model for group medical LOB', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::EAReferral, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();

    $this->actingAs($user);

    $response = $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::GroupMedical,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test.gm@example.com',
        'mobile_no' => '0501234574',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrorFor('quote_type_id');
});

it('allows collaborate model for group medical LOB with GM_ADVISOR role', function () {
    Queue::fake();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::GMAdvisor, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::GMAdvisor, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();

    $this->actingAs($user);

    $response = $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::GroupMedical,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test.gm.advisor@example.com',
        'mobile_no' => '0501234575',
    ]);

    $response->assertOk()->assertJsonPath('success', true);
});

it('allows collaborate model for health LOB with RM_ADVISOR role', function () {
    Queue::fake();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::RMAdvisor, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::RMAdvisor, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();

    $this->actingAs($user);

    $response = $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::Health,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test.health.rm@example.com',
        'mobile_no' => '0501234576',
        'health_plan_type_id' => null,
    ]);

    $response->assertOk()->assertJsonPath('success', true);
});

it('blocks collaborate model for life LOB without LIFE_ADVISOR role', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::EAReferral, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();

    $this->actingAs($user);

    $response = $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::Life,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test.life@example.com',
        'mobile_no' => '0501234577',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrorFor('quote_type_id');
});

it('allows collaborate model for life LOB with LIFE_ADVISOR role', function () {
    Queue::fake();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::LifeAdvisor, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::LifeAdvisor, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();

    $this->actingAs($user);

    $response = $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::Life,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test.life.advisor@example.com',
        'mobile_no' => '0501234578',
    ]);

    $response->assertOk()->assertJsonPath('success', true);
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
