<?php

declare(strict_types=1);

use App\Enums\EaModelEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Http\Middleware\PreventRequestForgery;
use App\Jobs\SendEAManagerDecisionEmailJob;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Services\EAManagerService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createEaSchema();
    $this->withoutMiddleware(PreventRequestForgery::class);
});

// ─── E1-E2 / Access control ───────────────────────────────────────────────────

it('returns 403 for non-EA-manager user on dashboard', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $this->getJson(route('ea-manager.index'))->assertStatus(403);
});

it('EA manager can access the dashboard', function () {
    $manager = TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($manager);

    $this->get(route('ea-manager.index'))->assertOk();
});

// ─── E7 / Pending rejection count ─────────────────────────────────────────────

it('pending rejections count includes leads with any rejection timestamp', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-rej-001',
        'quote_type_id' => 20, 'first_name' => 'Test', 'last_name' => 'Lead',
        'email' => 'rej1@example.com', 'mobile_no' => '0501110001',
        'source' => LeadSourceEnum::EA_IMCRM, 'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id, 'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    CarQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CAR-rej-001',
        'first_name' => 'Test', 'last_name' => 'Lead',
        'email' => 'rej2@example.com', 'mobile_no' => '0501110002',
        'source' => LeadSourceEnum::EA_IMCRM, 'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id, 'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_expert_advisor_rejected_at' => now(),
    ]);

    // Already managed by EA Manager — must not be counted
    PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-managed-001',
        'quote_type_id' => 20, 'first_name' => 'Managed', 'last_name' => 'Lead',
        'email' => 'managed@example.com', 'mobile_no' => '0501110003',
        'source' => LeadSourceEnum::EA_IMCRM, 'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id, 'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_assigned_advisor_rejected_at' => now(),
        'ea_manager_approved_at' => now(), // already actioned — excluded
    ]);

    expect(app(EAManagerService::class)->pendingRejectionsCount())->toBe(2);
});

it('pending rejections count endpoint returns correct count via HTTP', function () {
    $manager = TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => fake()->unique()->safeEmail()]);
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-rej-002',
        'quote_type_id' => 20, 'first_name' => 'Test', 'last_name' => 'Lead',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0501220001',
        'source' => LeadSourceEnum::EA_IMCRM, 'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id, 'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    $this->actingAs($manager);
    $this->getJson(route('ea-manager.pending-rejections'))
        ->assertOk()->assertJsonPath('count', 1);
});

// ─── E3 / Model-based filtering ───────────────────────────────────────────────

it('EA Manager can filter leads by ea_model', function () {
    $manager = TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => fake()->unique()->safeEmail()]);
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-ref-001',
        'quote_type_id' => 20, 'first_name' => 'Ref', 'last_name' => 'Lead',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0502220001',
        'source' => LeadSourceEnum::EA_IMCRM, 'ea_model' => 'referral',
        'advisor_id' => $advisor->id, 'quote_status_id' => QuoteStatusEnum::NewLead,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    $service = app(EAManagerService::class);
    $leads = $service->getLeads(['ea_model' => 'referral', 'status' => 'rejected']);

    expect($leads)->toHaveCount(1)
        ->and($leads->first()['ea_model'])->toBe('referral');
});

// ─── E3 / Status filter (Gap 3 fix) ──────────────────────────────────────────

it('EA Manager can filter leads by status=rejected', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-rej-s01',
        'quote_type_id' => 20, 'first_name' => 'Rej', 'last_name' => 'Lead',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0503330001',
        'source' => LeadSourceEnum::EA_IMCRM, 'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id, 'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    // Non-rejected lead
    PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-ok-s01',
        'quote_type_id' => 20, 'first_name' => 'Ok', 'last_name' => 'Lead',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0503330002',
        'source' => LeadSourceEnum::EA_IMCRM, 'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id, 'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_manager_approved_at' => now(),
    ]);

    $leads = app(EAManagerService::class)->getLeads(['status' => 'rejected']);
    expect($leads)->toHaveCount(1)
        ->and($leads->first()['has_rejection'])->toBeTrue();
});

it('EA Manager can filter leads by status=approved', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-appr-s01',
        'quote_type_id' => 20, 'first_name' => 'App', 'last_name' => 'Lead',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0503340001',
        'source' => LeadSourceEnum::EA_IMCRM, 'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id, 'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_manager_approved_at' => now(),
    ]);

    $leads = app(EAManagerService::class)->getLeads(['status' => 'approved']);
    expect($leads)->toHaveCount(1)
        ->and($leads->first()['ea_manager_approved_at'])->not->toBeNull();
});

// ─── E4 / Rejected collaborative leads shown with blank model ─────────────────

it('rejected collaborate lead has_rejection flag set', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-hasrej-001',
        'quote_type_id' => 20, 'first_name' => 'HasRej', 'last_name' => 'Lead',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0504440001',
        'source' => LeadSourceEnum::EA_IMCRM, 'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id, 'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    $leads = app(EAManagerService::class)->getLeads(['status' => 'rejected']);
    expect($leads->first()['has_rejection'])->toBeTrue();
});

// ─── E5 / Approve action ──────────────────────────────────────────────────────


it('EA manager approve dispatches decision email to both advisors (F3)', function () {
    Queue::fake();
    $manager = TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => fake()->unique()->safeEmail()]);
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expert = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-mgr-002',
        'quote_type_id' => 20, 'first_name' => 'Email', 'last_name' => 'Test',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0501330002',
        'source' => LeadSourceEnum::EA_IMCRM, 'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id, 'expert_advisor_id' => $expert->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    $this->actingAs($manager);
    $this->postJson(route('ea-manager.decision', ['quoteType' => 'personal', 'quoteId' => $lead->id]), ['action' => 'approve']);

    Queue::assertPushed(SendEAManagerDecisionEmailJob::class, fn ($job) => true);
});


// ─── E6 / Model change: Collaborative → Referral ──────────────────────────────

it('model change swaps advisor fields correctly and does NOT dispatch email (Gap 7 fix)', function () {
    Queue::fake();
    $manager = TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => fake()->unique()->safeEmail()]);
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expert = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-chg-001',
        'quote_type_id' => 20, 'first_name' => 'Test', 'last_name' => 'Lead',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0501440001',
        'source' => LeadSourceEnum::EA_IMCRM, 'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id, 'expert_advisor_id' => $expert->id,
        'lead_generator_id' => $advisor->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    $this->actingAs($manager);
    $this->patchJson(
        route('ea-manager.change-model', ['quoteType' => 'personal', 'quoteId' => $lead->id]),
        ['ea_model' => 'referral']
    )->assertOk()->assertJsonPath('success', true);

    $lead->refresh();
    // EA Advisor → Assigned Advisor; old Assigned Advisor → Lead Generator (FRD E6)
    expect($lead->ea_model)->toBe(EaModelEnum::Referral)
        ->and($lead->advisor_id)->toBe($expert->id)          // EA Advisor becomes Assigned
        ->and($lead->lead_generator_id)->toBe($advisor->id)  // old Assigned becomes Lead Generator
        ->and($lead->expert_advisor_id)->toBeNull()
        ->and($lead->ea_assigned_advisor_approved_at)->toBeNull()
        ->and($lead->ea_expert_advisor_approved_at)->toBeNull()
        ->and($lead->ea_assigned_advisor_rejected_at)->toBeNull()
        ->and($lead->ea_expert_advisor_rejected_at)->toBeNull();

    Queue::assertNotPushed(SendEAManagerDecisionEmailJob::class); // Gap 7: no email on model change
});

// ─── BusinessQuote (Corpline / GroupMedical) support ─────────────────────────

it('EA manager can approve a BusinessQuote (Corpline/GroupMedical) via ea-manager.decision', function () {
    Queue::fake();
    $manager = TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => fake()->unique()->safeEmail()]);
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expert = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    $lead = BusinessQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'BUS-mgr-001',
        'first_name' => 'Test',
        'last_name' => 'Lead',
        'email' => fake()->unique()->safeEmail(),
        'mobile_no' => '0501550001',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id,
        'expert_advisor_id' => $expert->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    $this->actingAs($manager);
    $this->postJson(route('ea-manager.decision', ['quoteType' => 'business', 'quoteId' => $lead->id]), ['action' => 'approve'])
        ->assertOk()->assertJsonPath('success', true);

    $lead->refresh();
    expect($lead->ea_manager_approved_at)->not->toBeNull()
        ->and($lead->ea_manager_id)->toBe($manager->id);
});


// ─── E9 / Export access ───────────────────────────────────────────────────────

it('EA manager can access the export endpoint', function () {
    $manager = TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($manager);

    $this->get(route('ea-manager.export'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
