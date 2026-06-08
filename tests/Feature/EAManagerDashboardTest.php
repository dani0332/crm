<?php

declare(strict_types=1);

use App\Enums\EaModelEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Http\Middleware\PreventRequestForgery;
use App\Jobs\SendEAManagerDecisionEmailJob;
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

it('returns 403 for non-EA-manager user on dashboard index', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($user);

    $response = $this->getJson(route('ea-manager.index'));
    $response->assertStatus(403);
});

it('ea manager can access dashboard', function () {
    $manager = TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($manager);

    $response = $this->get(route('ea-manager.index'));
    $response->assertOk();
});

it('pending rejections count includes leads with any rejection timestamp', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-rej-001',
        'quote_type_id' => 20,
        'first_name' => 'Test', 'last_name' => 'Lead',
        'email' => 'rej1@example.com', 'mobile_no' => '0501110001',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    CarQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CAR-rej-001',
        'first_name' => 'Test', 'last_name' => 'Lead',
        'email' => 'rej2@example.com', 'mobile_no' => '0501110002',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_expert_advisor_rejected_at' => now(),
    ]);

    // Lead without rejection — should not be counted
    PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-ok-001',
        'quote_type_id' => 20,
        'first_name' => 'Ok', 'last_name' => 'Lead',
        'email' => 'ok@example.com', 'mobile_no' => '0501110003',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    $count = app(EAManagerService::class)->pendingRejectionsCount();
    expect($count)->toBe(2);
});

it('pending rejections count endpoint returns correct count', function () {
    $manager = TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => fake()->unique()->safeEmail()]);
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-rej-002',
        'quote_type_id' => 20,
        'first_name' => 'Test', 'last_name' => 'Lead',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0501220001',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    $this->actingAs($manager);
    $response = $this->getJson(route('ea-manager.pending-rejections'));
    $response->assertOk()->assertJsonPath('count', 1);
});

it('manager approve force-approves both advisor timestamps', function () {
    Queue::fake();

    $manager = TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => fake()->unique()->safeEmail()]);
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expertAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-mgr-001',
        'quote_type_id' => 20,
        'first_name' => 'Test', 'last_name' => 'Lead',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0501330001',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id,
        'expert_advisor_id' => $expertAdvisor->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    $this->actingAs($manager);
    $response = $this->postJson(route('ea-manager.decision', ['quoteType' => 'personal', 'quoteId' => $lead->id]), ['action' => 'approve']);

    $response->assertOk()->assertJsonPath('success', true);
    $lead->refresh();
    expect($lead->ea_assigned_advisor_approved_at)->not->toBeNull();
    expect($lead->ea_expert_advisor_approved_at)->not->toBeNull();

    Queue::assertPushed(SendEAManagerDecisionEmailJob::class);
});

it('change model swaps fields correctly: collaborate to referral', function () {
    Queue::fake();

    $manager = TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => fake()->unique()->safeEmail()]);
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expertAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-chg-001',
        'quote_type_id' => 20,
        'first_name' => 'Test', 'last_name' => 'Lead',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0501440001',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id,
        'expert_advisor_id' => $expertAdvisor->id,
        'lead_generator_id' => $advisor->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    $this->actingAs($manager);
    $response = $this->patchJson(
        route('ea-manager.change-model', ['quoteType' => 'personal', 'quoteId' => $lead->id]),
        ['ea_model' => 'referral']
    );

    $response->assertOk()->assertJsonPath('success', true);

    $lead->refresh();
    expect($lead->ea_model)->toBe(EaModelEnum::Referral);
    expect($lead->advisor_id)->toBe($expertAdvisor->id);
    expect($lead->lead_generator_id)->toBe($advisor->id);
    expect($lead->expert_advisor_id)->toBeNull();
    expect($lead->ea_assigned_advisor_approved_at)->toBeNull();
    expect($lead->ea_expert_advisor_approved_at)->toBeNull();
    expect($lead->ea_assigned_advisor_rejected_at)->toBeNull();
    expect($lead->ea_expert_advisor_rejected_at)->toBeNull();

    Queue::assertPushed(SendEAManagerDecisionEmailJob::class);
});
