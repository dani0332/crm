<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Http\Middleware\PreventRequestForgery;
use App\Jobs\SendEACollaborateRejectedEmailJob;
use App\Models\PersonalQuote;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createEaSchema();
    $this->withoutMiddleware(PreventRequestForgery::class);
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function makeCollaborateLead(int $advisorId, int $expertAdvisorId): PersonalQuote
{
    return PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-'.Str::uuid()->toString(),
        'quote_type_id' => 20,
        'first_name' => 'Test',
        'last_name' => 'Client',
        'email' => fake()->unique()->safeEmail(),
        'mobile_no' => '050'.fake()->numerify('#######'),
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'advisor_id' => $advisorId,
        'expert_advisor_id' => $expertAdvisorId,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);
}

// ─── D9 / Button visibility guards ───────────────────────────────────────────

it('returns 422 when lead is not a collaborate EA lead', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-normal-001',
        'quote_type_id' => 20,
        'first_name' => 'Normal', 'last_name' => 'Lead',
        'email' => 'normal@example.com', 'mobile_no' => '0501112222',
        'source' => 'IMCRM',
        'ea_model' => null,
        'advisor_id' => $advisor->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    $this->actingAs($advisor);
    $this->postJson(route('ea-leads.approve', ['quoteType' => 'personal', 'quoteId' => $lead->id]))
        ->assertStatus(422);
});

it('returns 422 when lead status is not PolicyIssued', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expert = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-wrong-status',
        'quote_type_id' => 20,
        'first_name' => 'Wrong', 'last_name' => 'Status',
        'email' => fake()->unique()->safeEmail(), 'mobile_no' => '0501113333',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id,
        'expert_advisor_id' => $expert->id,
        'quote_status_id' => QuoteStatusEnum::NewLead, // not PolicyIssued
    ]);

    $this->actingAs($advisor);
    $this->postJson(route('ea-leads.approve', ['quoteType' => 'personal', 'quoteId' => $lead->id]))
        ->assertStatus(422);
});

// ─── D8 / Approve flow ────────────────────────────────────────────────────────

it('assigned advisor can approve and approval timestamp is recorded', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expert = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $lead = makeCollaborateLead($advisor->id, $expert->id);

    $this->actingAs($advisor);
    $this->postJson(route('ea-leads.approve', ['quoteType' => 'personal', 'quoteId' => $lead->id]))
        ->assertOk()->assertJsonPath('success', true);

    $lead->refresh();
    expect($lead->ea_assigned_advisor_approved_at)->not->toBeNull()
        ->and($lead->ea_expert_advisor_approved_at)->toBeNull();
});

it('expert advisor can approve and approval timestamp is recorded', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expert = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $lead = makeCollaborateLead($advisor->id, $expert->id);

    $this->actingAs($expert);
    $this->postJson(route('ea-leads.approve', ['quoteType' => 'personal', 'quoteId' => $lead->id]))
        ->assertOk();

    $lead->refresh();
    expect($lead->ea_expert_advisor_approved_at)->not->toBeNull()
        ->and($lead->ea_assigned_advisor_approved_at)->toBeNull();
});

it('lead advances to PolicyBooked when both advisors approve', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expert = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $lead = makeCollaborateLead($advisor->id, $expert->id);

    $this->actingAs($advisor);
    $this->postJson(route('ea-leads.approve', ['quoteType' => 'personal', 'quoteId' => $lead->id]));

    $this->actingAs($expert);
    $this->postJson(route('ea-leads.approve', ['quoteType' => 'personal', 'quoteId' => $lead->id]));

    $lead->refresh();
    expect($lead->quote_status_id)->toBe(QuoteStatusEnum::PolicyBooked)
        ->and($lead->ea_assigned_advisor_approved_at)->not->toBeNull()
        ->and($lead->ea_expert_advisor_approved_at)->not->toBeNull();
});

it('unrelated user cannot approve and gets 403', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expert = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $intruder = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $lead = makeCollaborateLead($advisor->id, $expert->id);

    $this->actingAs($intruder);
    $this->postJson(route('ea-leads.approve', ['quoteType' => 'personal', 'quoteId' => $lead->id]))
        ->assertStatus(403);

    $lead->refresh();
    expect($lead->ea_assigned_advisor_approved_at)->toBeNull();
});

// ─── D10 / Rejection escalation ───────────────────────────────────────────────

it('rejection records timestamp and dispatches SendEACollaborateRejectedEmailJob', function () {
    Queue::fake();
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expert = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $lead = makeCollaborateLead($advisor->id, $expert->id);

    $this->actingAs($advisor);
    $this->postJson(route('ea-leads.reject', ['quoteType' => 'personal', 'quoteId' => $lead->id]))
        ->assertOk();

    $lead->refresh();
    expect($lead->ea_assigned_advisor_rejected_at)->not->toBeNull();
    Queue::assertPushed(SendEACollaborateRejectedEmailJob::class);
});

it('expert advisor rejection records its own timestamp', function () {
    Queue::fake();
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expert = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $lead = makeCollaborateLead($advisor->id, $expert->id);

    $this->actingAs($expert);
    $this->postJson(route('ea-leads.reject', ['quoteType' => 'personal', 'quoteId' => $lead->id]))
        ->assertOk();

    $lead->refresh();
    expect($lead->ea_expert_advisor_rejected_at)->not->toBeNull()
        ->and($lead->ea_assigned_advisor_rejected_at)->toBeNull();
    Queue::assertPushed(SendEACollaborateRejectedEmailJob::class);
});
