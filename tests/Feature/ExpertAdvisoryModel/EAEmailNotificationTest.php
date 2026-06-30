<?php

declare(strict_types=1);

use App\Enums\EaModelEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Jobs\SendEACollaborateRejectedEmailJob;
use App\Jobs\SendEALeadSubmittedEmailJob;
use App\Jobs\SendEAManagerDecisionEmailJob;
use App\Models\PersonalQuote;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createEaSchema();
    config(['constants.SIB_URL' => 'https://api.brevo.com/v3/smtp/email']);
    Http::fake(['*' => Http::response(null, 201)]);
});

// ─── F1 / Lead submission email ───────────────────────────────────────────────

it('SendEALeadSubmittedEmailJob sends to advisor with lead generator and managers in CC', function () {
    $advisor = TestDataSeeder::createUser(['email' => 'advisor@example.com', 'name' => 'Advisor One']);
    $leadGen = TestDataSeeder::createUser(['email' => 'leadgen@example.com', 'name' => 'Lead Gen']);
    TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => 'manager@example.com', 'name' => 'Manager']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-email-001',
        'quote_type_id' => 20, 'first_name' => 'Email', 'last_name' => 'Test',
        'email' => 'customer@example.com', 'mobile_no' => '0501000001',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => EaModelEnum::Referral,
        'advisor_id' => $advisor->id,
        'lead_generator_id' => $leadGen->id,
        'quote_status_id' => QuoteStatusEnum::NewLead,
    ]);

    (new SendEALeadSubmittedEmailJob($lead, 'personal', 999))->handle();

    Http::assertSent(function ($request) use ($advisor, $leadGen) {
        $body = $request->data();
        $ccEmails = array_column($body['cc'] ?? [], 'email');

        return $body['to'][0]['email'] === $advisor->email
            && in_array($leadGen->email, $ccEmails)
            && in_array('manager@example.com', $ccEmails)
            && $body['templateId'] === 999;
    });
});

it('SendEALeadSubmittedEmailJob skips sending when advisor has no email', function () {
    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-email-002',
        'quote_type_id' => 20, 'first_name' => 'No', 'last_name' => 'Advisor',
        'email' => 'noone@example.com', 'mobile_no' => '0501000002',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => EaModelEnum::Referral,
        'advisor_id' => null,
        'quote_status_id' => QuoteStatusEnum::NewLead,
    ]);

    (new SendEALeadSubmittedEmailJob($lead, 'personal', 999))->handle();

    Http::assertNothingSent();
});

it('SendEALeadSubmittedEmailJob includes eaAdvisor param for Collaborate ea model', function () {
    $advisor = TestDataSeeder::createUser(['email' => 'advisor-collab@example.com', 'name' => 'Advisor Collab']);
    $expert = TestDataSeeder::createUser(['email' => 'expert-collab@example.com', 'name' => 'Expert Collab']);
    TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => 'manager-collab@example.com']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-email-collab-01',
        'quote_type_id' => 20, 'first_name' => 'Collab', 'last_name' => 'Test',
        'email' => 'customer-collab@example.com', 'mobile_no' => '0501000010',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => EaModelEnum::Collaborate,
        'advisor_id' => $advisor->id,
        'expert_advisor_id' => $expert->id,
        'quote_status_id' => QuoteStatusEnum::NewLead,
    ]);

    (new SendEALeadSubmittedEmailJob($lead, 'personal', 999))->handle();

    Http::assertSent(function ($request) use ($expert) {
        $params = $request->data()['params'] ?? [];

        return isset($params['eaAdvisor']) && $params['eaAdvisor'] === $expert->name;
    });
});

it('SendEALeadSubmittedEmailJob does not include eaAdvisor param for non-Collaborate ea model', function () {
    $advisor = TestDataSeeder::createUser(['email' => 'advisor-ref@example.com', 'name' => 'Advisor Ref']);
    TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => 'manager-ref@example.com']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-email-ref-01',
        'quote_type_id' => 20, 'first_name' => 'Ref', 'last_name' => 'Test',
        'email' => 'customer-ref@example.com', 'mobile_no' => '0501000011',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => EaModelEnum::Referral,
        'advisor_id' => $advisor->id,
        'quote_status_id' => QuoteStatusEnum::NewLead,
    ]);

    (new SendEALeadSubmittedEmailJob($lead, 'personal', 999))->handle();

    Http::assertSent(function ($request) {
        $params = $request->data()['params'] ?? [];

        return ! array_key_exists('eaAdvisor', $params);
    });
});

it('SendEALeadSubmittedEmailJob skips sending when template ID is not configured', function () {
    $advisor = TestDataSeeder::createUser(['email' => 'adv-skip@example.com']);
    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-email-skip',
        'quote_type_id' => 20, 'first_name' => 'Skip', 'last_name' => 'Tpl',
        'email' => 'customer-skip@example.com', 'mobile_no' => '0501000099',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => EaModelEnum::Referral,
        'advisor_id' => $advisor->id,
        'quote_status_id' => QuoteStatusEnum::NewLead,
    ]);

    // Template ID 0 simulates "not configured"
    (new SendEALeadSubmittedEmailJob($lead, 'personal', 0))->handle();

    Http::assertNothingSent();
});

// ─── F2 / Rejection email to managers ────────────────────────────────────────

it('SendEACollaborateRejectedEmailJob sends to EA managers with advisor details in params', function () {
    $advisor = TestDataSeeder::createUser(['email' => 'adv@example.com', 'name' => 'Advisor']);
    $expert = TestDataSeeder::createUser(['email' => 'expert@example.com', 'name' => 'Expert']);
    TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => 'manager@example.com', 'name' => 'Manager']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-email-003',
        'quote_type_id' => 20, 'first_name' => 'Rej', 'last_name' => 'Test',
        'email' => 'customer2@example.com', 'mobile_no' => '0501000003',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => EaModelEnum::Collaborate,
        'advisor_id' => $advisor->id,
        'expert_advisor_id' => $expert->id,
        'quote_status_id' => QuoteStatusEnum::PaymentPending,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    (new SendEACollaborateRejectedEmailJob($lead, 'personal', 999))->handle();

    Http::assertSent(function ($request) {
        $body = $request->data();
        $toEmails = array_column($body['to'], 'email');

        return in_array('manager@example.com', $toEmails)
            && isset($body['params']['advisorName'])
            && isset($body['params']['eaAdvisor'])
            && $body['templateId'] === 999;
    });
});

it('SendEACollaborateRejectedEmailJob skips when no EA managers exist', function () {
    $advisor = TestDataSeeder::createUser(['email' => 'adv2@example.com', 'name' => 'Advisor 2']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-email-004',
        'quote_type_id' => 20, 'first_name' => 'No', 'last_name' => 'Manager',
        'email' => 'cust3@example.com', 'mobile_no' => '0501000004',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => EaModelEnum::Collaborate,
        'advisor_id' => $advisor->id,
        'quote_status_id' => QuoteStatusEnum::PaymentPending,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    (new SendEACollaborateRejectedEmailJob($lead, 'personal', 999))->handle();

    Http::assertNothingSent();
});

// ─── F3 / EA Manager approval email ──────────────────────────────────────────

it('SendEAManagerDecisionEmailJob sends to both advisors with Approved status', function () {
    $advisor = TestDataSeeder::createUser(['email' => 'advisor3@example.com', 'name' => 'Advisor 3']);
    $expert = TestDataSeeder::createUser(['email' => 'expert3@example.com', 'name' => 'Expert 3']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-email-005',
        'quote_type_id' => 20, 'first_name' => 'Dec', 'last_name' => 'Test',
        'email' => 'cust4@example.com', 'mobile_no' => '0501000005',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => EaModelEnum::Collaborate,
        'advisor_id' => $advisor->id,
        'expert_advisor_id' => $expert->id,
        'quote_status_id' => QuoteStatusEnum::PaymentPending,
    ]);

    (new SendEAManagerDecisionEmailJob($lead, 'personal', 'Approved', 999))->handle();

    Http::assertSent(function ($request) {
        $body = $request->data();
        $toEmails = array_column($body['to'], 'email');

        return $body['params']['status'] === 'Approved'
            && in_array('advisor3@example.com', $toEmails)
            && in_array('expert3@example.com', $toEmails)
            && $body['templateId'] === 999;
    });
});

it('SendEAManagerDecisionEmailJob skips when no recipients can be resolved', function () {
    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(), 'code' => 'CYB-email-no-recip',
        'quote_type_id' => 20, 'first_name' => 'No', 'last_name' => 'Recip',
        'email' => 'cust-norecip@example.com', 'mobile_no' => '0501000098',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => EaModelEnum::Collaborate,
        'advisor_id' => null,
        'expert_advisor_id' => null,
        'quote_status_id' => QuoteStatusEnum::PaymentPending,
    ]);

    (new SendEAManagerDecisionEmailJob($lead, 'personal', 'Approved', 999))->handle();

    Http::assertNothingSent();
});
