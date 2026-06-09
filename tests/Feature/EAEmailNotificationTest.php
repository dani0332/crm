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
    Http::fake(['*' => Http::response(null, 201)]);
});

it('SendEALeadSubmittedEmailJob sends Brevo email with advisor as recipient and lead generator + managers in CC', function () {
    $advisor = TestDataSeeder::createUser(['email' => 'advisor@example.com', 'name' => 'Advisor One']);
    $leadGen = TestDataSeeder::createUser(['email' => 'leadgen@example.com', 'name' => 'Lead Gen']);
    TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => 'manager@example.com', 'name' => 'Manager']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-email-001',
        'quote_type_id' => 20,
        'first_name' => 'Email', 'last_name' => 'Test',
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
        $ccEmails = array_column($body['cc'], 'email');

        return $body['to'][0]['email'] === $advisor->email
            && in_array($leadGen->email, $ccEmails)
            && in_array('manager@example.com', $ccEmails)
            && $body['templateId'] === 999;
    });
});

it('SendEALeadSubmittedEmailJob does not send if advisor has no email', function () {
    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-email-002',
        'quote_type_id' => 20,
        'first_name' => 'No', 'last_name' => 'Advisor',
        'email' => 'noone@example.com', 'mobile_no' => '0501000002',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => EaModelEnum::Referral,
        'advisor_id' => null,
        'quote_status_id' => QuoteStatusEnum::NewLead,
    ]);

    (new SendEALeadSubmittedEmailJob($lead, 'personal', 999))->handle();

    Http::assertNothingSent();
});

it('SendEACollaborateRejectedEmailJob sends Brevo email to EA managers with advisor details in params', function () {
    $advisor = TestDataSeeder::createUser(['email' => 'adv@example.com', 'name' => 'Advisor']);
    $expertAdvisor = TestDataSeeder::createUser(['email' => 'expert@example.com', 'name' => 'Expert']);
    TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => 'manager@example.com', 'name' => 'Manager']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-email-003',
        'quote_type_id' => 20,
        'first_name' => 'Rej', 'last_name' => 'Test',
        'email' => 'customer2@example.com', 'mobile_no' => '0501000003',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => EaModelEnum::Collaborate,
        'advisor_id' => $advisor->id,
        'expert_advisor_id' => $expertAdvisor->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    (new SendEACollaborateRejectedEmailJob($lead, 'personal', 999))->handle();

    Http::assertSent(function ($request) {
        $body = $request->data();
        $toEmails = array_column($body['to'], 'email');

        return in_array('manager@example.com', $toEmails)
            && $body['params']['advisor_email'] === 'adv@example.com'
            && $body['params']['expert_advisor_email'] === 'expert@example.com'
            && $body['templateId'] === 999;
    });
});

it('SendEACollaborateRejectedEmailJob skips sending when no EA managers exist', function () {
    $advisor = TestDataSeeder::createUser(['email' => 'adv2@example.com', 'name' => 'Advisor 2']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-email-004',
        'quote_type_id' => 20,
        'first_name' => 'No', 'last_name' => 'Manager',
        'email' => 'cust3@example.com', 'mobile_no' => '0501000004',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => EaModelEnum::Collaborate,
        'advisor_id' => $advisor->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    (new SendEACollaborateRejectedEmailJob($lead, 'personal', 999))->handle();

    Http::assertNothingSent();
});

it('SendEAManagerDecisionEmailJob sends Brevo email to both advisors with decision in params', function () {
    $advisor = TestDataSeeder::createUser(['email' => 'advisor3@example.com', 'name' => 'Advisor 3']);
    $expertAdvisor = TestDataSeeder::createUser(['email' => 'expert3@example.com', 'name' => 'Expert 3']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-email-005',
        'quote_type_id' => 20,
        'first_name' => 'Dec', 'last_name' => 'Test',
        'email' => 'cust4@example.com', 'mobile_no' => '0501000005',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => EaModelEnum::Collaborate,
        'advisor_id' => $advisor->id,
        'expert_advisor_id' => $expertAdvisor->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    (new SendEAManagerDecisionEmailJob($lead, 'personal', 'Approved', 999))->handle();

    Http::assertSent(function ($request) {
        $body = $request->data();
        $toEmails = array_column($body['to'], 'email');

        return $body['params']['decision'] === 'Approved'
            && in_array('advisor3@example.com', $toEmails)
            && in_array('expert3@example.com', $toEmails)
            && $body['templateId'] === 999;
    });
});

it('SendEAManagerDecisionEmailJob sends with model-change decision', function () {
    $advisor = TestDataSeeder::createUser(['email' => 'advisor4@example.com', 'name' => 'Advisor 4']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-email-006',
        'quote_type_id' => 20,
        'first_name' => 'Chg', 'last_name' => 'Test',
        'email' => 'cust5@example.com', 'mobile_no' => '0501000006',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => EaModelEnum::Referral,
        'advisor_id' => $advisor->id,
        'expert_advisor_id' => null,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    (new SendEAManagerDecisionEmailJob($lead, 'personal', 'Model Changed to Referral', 999))->handle();

    Http::assertSent(function ($request) {
        return $request->data()['params']['decision'] === 'Model Changed to Referral';
    });
});
