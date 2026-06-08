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

it('assigned advisor can approve and records timestamp', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expertAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $lead = makeCollaborateLead($advisor->id, $expertAdvisor->id);

    $this->actingAs($advisor);

    $response = $this->postJson(route('ea-leads.approve', ['quoteType' => 'personal', 'quoteId' => $lead->id]));

    $response->assertOk()->assertJsonPath('success', true);

    $lead->refresh();
    expect($lead->ea_assigned_advisor_approved_at)->not->toBeNull();
    expect($lead->ea_expert_advisor_approved_at)->toBeNull();
});

it('expert advisor can approve and records timestamp', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expertAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $lead = makeCollaborateLead($advisor->id, $expertAdvisor->id);

    $this->actingAs($expertAdvisor);

    $response = $this->postJson(route('ea-leads.approve', ['quoteType' => 'personal', 'quoteId' => $lead->id]));

    $response->assertOk();
    $lead->refresh();
    expect($lead->ea_expert_advisor_approved_at)->not->toBeNull();
    expect($lead->ea_assigned_advisor_approved_at)->toBeNull();
});

it('lead progresses to PolicyBooked when both advisors approve', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expertAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $lead = makeCollaborateLead($advisor->id, $expertAdvisor->id);

    $this->actingAs($advisor);
    $this->postJson(route('ea-leads.approve', ['quoteType' => 'personal', 'quoteId' => $lead->id]));

    $this->actingAs($expertAdvisor);
    $this->postJson(route('ea-leads.approve', ['quoteType' => 'personal', 'quoteId' => $lead->id]));

    $lead->refresh();
    expect($lead->quote_status_id)->toBe(QuoteStatusEnum::PolicyBooked);
    expect($lead->ea_assigned_advisor_approved_at)->not->toBeNull();
    expect($lead->ea_expert_advisor_approved_at)->not->toBeNull();
});

it('unrelated user cannot approve and gets 403', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expertAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $intruder = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $lead = makeCollaborateLead($advisor->id, $expertAdvisor->id);

    $this->actingAs($intruder);
    $response = $this->postJson(route('ea-leads.approve', ['quoteType' => 'personal', 'quoteId' => $lead->id]));

    $response->assertStatus(403);
    $lead->refresh();
    expect($lead->ea_assigned_advisor_approved_at)->toBeNull();
});

it('rejection records timestamp and dispatches email job', function () {
    Queue::fake();

    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $expertAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);
    $lead = makeCollaborateLead($advisor->id, $expertAdvisor->id);

    $this->actingAs($advisor);
    $response = $this->postJson(route('ea-leads.reject', ['quoteType' => 'personal', 'quoteId' => $lead->id]));

    $response->assertOk();
    $lead->refresh();
    expect($lead->ea_assigned_advisor_rejected_at)->not->toBeNull();

    Queue::assertPushed(SendEACollaborateRejectedEmailJob::class);
});

it('returns 422 when lead is not a collaborate EA lead', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::EAReferral, ['email' => fake()->unique()->safeEmail()]);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-normal-001',
        'quote_type_id' => 20,
        'first_name' => 'Normal',
        'last_name' => 'Lead',
        'email' => 'normal@example.com',
        'mobile_no' => '0501112222',
        'source' => 'IMCRM',
        'ea_model' => null,
        'advisor_id' => $advisor->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    $this->actingAs($advisor);
    $response = $this->postJson(route('ea-leads.approve', ['quoteType' => 'personal', 'quoteId' => $lead->id]));

    $response->assertStatus(422);
});
