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
use App\Models\User;
use App\Services\EALeadCapiService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

// ─── Setup ────────────────────────────────────────────────────────────────────

beforeEach(function () {
    TestSchemaCreator::createEaSchema();
    $this->withoutMiddleware(PreventRequestForgery::class);

    DB::connection('sqlite')->table('quote_type')->insertOrIgnore([
        ['id' => QuoteTypeId::Car, 'code' => 'Car', 'short_code' => 'CAR', 'text' => 'Car Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => QuoteTypeId::Health, 'code' => 'Health', 'short_code' => 'HLT', 'text' => 'Health Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => QuoteTypeId::Travel, 'code' => 'Travel', 'short_code' => 'TRV', 'text' => 'Travel Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => QuoteTypeId::Life, 'code' => 'Life', 'short_code' => 'LIF', 'text' => 'Life Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => QuoteTypeId::GroupMedical, 'code' => 'GroupMedical', 'short_code' => 'GMD', 'text' => 'Group Medical', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => QuoteTypeId::Cyber, 'code' => 'Cyber', 'short_code' => 'CYB', 'text' => 'Cyber Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => QuoteTypeId::Corpline, 'code' => 'Corpline', 'short_code' => 'CRL', 'text' => 'Corporate Line', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Stub CAPI so tests don't make real HTTP calls
    app()->bind(EALeadCapiService::class, function () {
        return new class extends EALeadCapiService
        {
            public function createLead(EALeadCreateRequest $request, int $quoteTypeId, bool $isCollaborate): mixed
            {
                $uuid = Str::uuid()->toString();
                $prefix = match ($quoteTypeId) {
                    QuoteTypeId::Car => 'CAR',
                    QuoteTypeId::Health => 'HEA',
                    QuoteTypeId::Life => 'LIF',
                    QuoteTypeId::Corpline,
                    QuoteTypeId::GroupMedical => 'BUS',
                    default => 'CYB',
                };

                $base = [
                    'uuid' => $uuid,
                    'code' => $prefix.'-'.$uuid,
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

                match ($quoteTypeId) {
                    QuoteTypeId::Car => CarQuote::create($base),
                    QuoteTypeId::Health => HealthQuote::create(array_merge($base, ['health_plan_type_id' => $request->health_plan_type_id])),
                    QuoteTypeId::Life => LifeQuote::create(array_merge($base, ['quote_type_id' => $quoteTypeId])),
                    QuoteTypeId::Corpline,
                    QuoteTypeId::GroupMedical => BusinessQuote::create(array_merge($base, ['business_type_of_insurance_id' => $request->business_type_of_insurance_id])),
                    default => PersonalQuote::create(array_merge($base, ['quote_type_id' => $quoteTypeId])),
                };

                return (object) ['quoteUID' => $uuid];
            }
        };
    });
});

// ─── A2 / Authorization ───────────────────────────────────────────────────────

it('returns 403 when user has no EA role or permission', function () {
    $user = TestDataSeeder::createUser(['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'referral',
        'quote_type_id' => QuoteTypeId::Cyber,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'mobile_no' => '0501234567',
    ])->assertStatus(403);
});

it('user with EA_REFERRAL role can access the lead creation endpoint', function () {
    Queue::fake();
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'referral',
        'quote_type_id' => QuoteTypeId::Cyber,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => fake()->unique()->safeEmail(),
        'mobile_no' => '0501234568',
    ])->assertOk();
});

it('user with EA_COLLABORATE permission can access the lead creation endpoint', function () {
    Queue::fake();
    $user = TestDataSeeder::createUser(['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions('EA_REFERRAL', ['ea-collaborate']);
    DB::connection('sqlite')->table('model_has_roles')->insertOrIgnore([
        'role_id' => DB::connection('sqlite')->table('roles')->where('name', 'EA_REFERRAL')->value('id'),
        'model_type' => User::class,
        'model_id' => $user->id,
    ]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();

    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::Cyber,
        'first_name' => 'Collab',
        'last_name' => 'User',
        'email' => fake()->unique()->safeEmail(),
        'mobile_no' => '0501234569',
    ])->assertOk();
});

// ─── A3 / EA Model dropdown ───────────────────────────────────────────────────

it('rejects invalid ea_model value', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'invalid_model',
        'quote_type_id' => QuoteTypeId::Cyber,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => fake()->unique()->safeEmail(),
        'mobile_no' => '0501234570',
    ])->assertStatus(422)->assertJsonValidationErrorFor('ea_model');
});

// ─── A5 / Lead source ─────────────────────────────────────────────────────────

it('creates a referral EA lead with source EA_IMCRM and correct lead_generator_id', function () {
    Queue::fake();
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $email = fake()->unique()->safeEmail();
    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'referral',
        'quote_type_id' => QuoteTypeId::Car,
        'first_name' => 'Lead',
        'last_name' => 'Gen',
        'email' => $email,
        'mobile_no' => '0501234571',
    ])->assertOk()->assertJsonPath('success', true);

    $lead = CarQuote::where('email', $email)->first();
    expect($lead)->not->toBeNull()
        ->and($lead->source)->toBe(LeadSourceEnum::EA_IMCRM)        // A5
        ->and($lead->ea_model)->toBe(EaModelEnum::Referral)
        ->and($lead->lead_generator_id)->toBe($user->id);           // C4
});

// ─── A9 / Manager role forced to Referral ─────────────────────────────────────

it('forces referral model for any manager role even when collaborate is submitted', function () {
    Queue::fake();
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::EAManager, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();
    $this->actingAs($user);

    $email = fake()->unique()->safeEmail();
    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::Cyber,
        'first_name' => 'Manager',
        'last_name' => 'Lead',
        'email' => $email,
        'mobile_no' => '0501234572',
    ])->assertOk();

    expect(PersonalQuote::where('email', $email)->first()->ea_model)->toBe(EaModelEnum::Referral);
});

// ─── A10 / Confirm button — handled by form validation (ea_model required) ────

it('rejects submission when ea_model is missing', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'quote_type_id' => QuoteTypeId::Cyber,
        'first_name' => 'No',
        'last_name' => 'Model',
        'email' => fake()->unique()->safeEmail(),
        'mobile_no' => '0501234573',
    ])->assertStatus(422)->assertJsonValidationErrorFor('ea_model');
});

// ─── B1 / Generic fields validation ──────────────────────────────────────────

it('rejects referral lead when first_name is missing', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'referral',
        'quote_type_id' => QuoteTypeId::Cyber,
        'last_name' => 'Smith',
        'email' => fake()->unique()->safeEmail(),
        'mobile_no' => '0501234574',
    ])->assertStatus(422)->assertJsonValidationErrorFor('first_name');
});

it('rejects referral lead when email is invalid', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'referral',
        'quote_type_id' => QuoteTypeId::Cyber,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'not-an-email',
        'mobile_no' => '0501234575',
    ])->assertStatus(422)->assertJsonValidationErrorFor('email');
});

// ─── B3 / Corpline requires Business Type of Insurance ───────────────────────

it('rejects corpline lead when business_type_of_insurance_id is missing', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'referral',
        'quote_type_id' => QuoteTypeId::Corpline,
        'first_name' => 'Corp',
        'last_name' => 'Lead',
        'email' => fake()->unique()->safeEmail(),
        'mobile_no' => '0501234576',
    ])->assertStatus(422)->assertJsonValidationErrorFor('business_type_of_insurance_id');
});

// ─── B4 / Health requires Plan Type (Gap 5 fix) ───────────────────────────────

it('rejects health referral lead when health_plan_type_id is missing', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'referral',
        'quote_type_id' => QuoteTypeId::Health,
        'first_name' => 'Health',
        'last_name' => 'Lead',
        'email' => fake()->unique()->safeEmail(),
        'mobile_no' => '0501234577',
    ])->assertStatus(422)->assertJsonValidationErrorFor('health_plan_type_id');
});

// ─── B5 / Duplicate check ─────────────────────────────────────────────────────

it('returns 422 with duplicate info when lead exists within 60 days', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $email = 'dup-60days@example.com';
    PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-dup-001',
        'quote_type_id' => QuoteTypeId::Cyber,
        'email' => $email,
        'mobile_no' => '0509991111',
        'source' => LeadSourceEnum::EA_IMCRM,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'first_name' => 'Dup',
        'last_name' => 'Test',
        'advisor_id' => $user->id,
    ]);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'referral',
        'quote_type_id' => QuoteTypeId::Cyber,
        'first_name' => 'Dup',
        'last_name' => 'Test',
        'email' => $email,
        'mobile_no' => '0509991111',
    ])->assertStatus(422)->assertJsonPath('duplicate', true);
});

it('does not block duplicate leads with status PolicyBooked', function () {
    Queue::fake();
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $email = fake()->unique()->safeEmail();
    PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-booked-001',
        'quote_type_id' => QuoteTypeId::Cyber,
        'email' => $email,
        'mobile_no' => '0509992222',
        'source' => LeadSourceEnum::EA_IMCRM,
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
        'first_name' => 'Booked',
        'last_name' => 'Lead',
        'advisor_id' => $user->id,
    ]);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'referral',
        'quote_type_id' => QuoteTypeId::Cyber,
        'first_name' => 'New',
        'last_name' => 'Lead',
        'email' => $email,
        'mobile_no' => '0509992222',
    ])->assertOk()->assertJsonPath('success', true);
});

// ─── B6 / Renewal upload source blocks lead ───────────────────────────────────

it('blocks creation when active renewal-upload lead exists with unexpired policy', function () {
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

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'referral',
        'quote_type_id' => QuoteTypeId::Car,
        'first_name' => 'Renew',
        'last_name' => 'Test',
        'email' => 'renewal@example.com',
        'mobile_no' => '0508888888',
    ])->assertStatus(422)->assertJsonPath('duplicate', true);
});

// ─── D1 / Collaborate lead submits email after creation ───────────────────────

it('dispatches submission email after referral lead creation', function () {
    Queue::fake();
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'referral',
        'quote_type_id' => QuoteTypeId::Car,
        'first_name' => 'Email',
        'last_name' => 'Test',
        'email' => fake()->unique()->safeEmail(),
        'mobile_no' => '0501234578',
    ])->assertOk();

    Queue::assertPushed(SendEALeadSubmittedEmailJob::class); // F1
});

// ─── D4 / LOB exclusions from Collaborative (Gap 1 + 2 fixes) ────────────────

it('blocks collaborate model for Car LOB (always excluded)', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::EAReferral, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::Car,
        'first_name' => 'Test', 'last_name' => 'User',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0501234580',
    ])->assertStatus(422)->assertJsonValidationErrorFor('quote_type_id');
});

it('blocks collaborate model for Travel LOB (always excluded)', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::EAReferral, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::Travel,
        'first_name' => 'Test', 'last_name' => 'User',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0501234581',
    ])->assertStatus(422)->assertJsonValidationErrorFor('quote_type_id');
});

it('blocks collaborate model for Health LOB even with RM_ADVISOR role (Gap 1 fix)', function () {
    // Health is completely excluded regardless of role (FRD D4 + user clarification)
    $user = TestDataSeeder::createUserWithRole(RolesEnum::RMAdvisor, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::RMAdvisor, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::Health,
        'first_name' => 'Test', 'last_name' => 'User',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0501234582',
    ])->assertStatus(422)->assertJsonValidationErrorFor('quote_type_id');
});

// ─── D2 / Life and GM require RM_ADVISOR for Collaborative (Gap 2 fix) ────────

it('blocks collaborate for Life LOB without RM_ADVISOR', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::EAReferral, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::Life,
        'first_name' => 'Test', 'last_name' => 'User',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0501234583',
    ])->assertStatus(422)->assertJsonValidationErrorFor('quote_type_id');
});

it('blocks collaborate for Group Medical LOB without RM_ADVISOR', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::EAReferral, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::GroupMedical,
        'first_name' => 'Test', 'last_name' => 'User',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0501234584',
    ])->assertStatus(422)->assertJsonValidationErrorFor('quote_type_id');
});

it('allows collaborate for Life LOB with RM_ADVISOR role', function () {
    Queue::fake();
    $user = TestDataSeeder::createUserWithRole(RolesEnum::RMAdvisor, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::RMAdvisor, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::Life,
        'first_name' => 'Test', 'last_name' => 'User',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0501234585',
    ])->assertOk()->assertJsonPath('success', true);
});

it('allows collaborate for Group Medical LOB with RM_ADVISOR role', function () {
    Queue::fake();
    $user = TestDataSeeder::createUserWithRole(RolesEnum::RMAdvisor, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::RMAdvisor, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();
    $this->actingAs($user);

    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::GroupMedical,
        'first_name' => 'Test', 'last_name' => 'User',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0501234586',
    ])->assertOk()->assertJsonPath('success', true);
});

// ─── D6 / Collaborate sets advisor_id to creating user ───────────────────────

it('creates a collaborate lead with advisor_id set to the creating user', function () {
    Queue::fake();
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions(RolesEnum::EAReferral, ['ea-collaborate']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->refresh();
    $this->actingAs($user);

    $email = fake()->unique()->safeEmail();
    $this->postJson(route('ea-leads.store'), [
        'ea_model' => 'collaborate',
        'quote_type_id' => QuoteTypeId::Cyber,
        'first_name' => 'Collab', 'last_name' => 'Lead',
        'email' => $email, 'mobile_no' => '0501234587',
    ])->assertOk()->assertJsonPath('success', true);

    $lead = PersonalQuote::where('email', $email)->first();
    expect($lead->advisor_id)->toBe($user->id)
        ->and($lead->ea_model)->toBe(EaModelEnum::Collaborate);
});
