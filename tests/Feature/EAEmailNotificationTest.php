<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Jobs\SendEACollaborateRejectedEmailJob;
use App\Jobs\SendEALeadSubmittedEmailJob;
use App\Jobs\SendEAManagerDecisionEmailJob;
use App\Mail\EACollaborateRejectedMail;
use App\Mail\EALeadSubmittedMail;
use App\Mail\EAManagerDecisionMail;
use App\Models\PersonalQuote;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

it('SendEALeadSubmittedEmailJob sends mail to advisor with lead generator in CC', function () {
    Mail::fake();

    $advisor = TestDataSeeder::createUser(['email' => 'advisor@example.com', 'name' => 'Advisor One']);
    $leadGen = TestDataSeeder::createUser(['email' => 'leadgen@example.com', 'name' => 'Lead Gen']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-email-001',
        'quote_type_id' => 20,
        'first_name' => 'Email', 'last_name' => 'Test',
        'email' => 'customer@example.com', 'mobile_no' => '0501000001',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'referral',
        'advisor_id' => $advisor->id,
        'lead_generator_id' => $leadGen->id,
        'quote_status_id' => QuoteStatusEnum::NewLead,
    ]);

    (new SendEALeadSubmittedEmailJob($lead, 'personal'))->handle();

    Mail::assertSent(EALeadSubmittedMail::class, function ($mail) {
        return $mail->hasTo('advisor@example.com')
            && $mail->hasCc('leadgen@example.com');
    });
});

it('SendEALeadSubmittedEmailJob does not send if advisor has no email', function () {
    Mail::fake();

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-email-002',
        'quote_type_id' => 20,
        'first_name' => 'No', 'last_name' => 'Advisor',
        'email' => 'noone@example.com', 'mobile_no' => '0501000002',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'referral',
        'advisor_id' => null,
        'quote_status_id' => QuoteStatusEnum::NewLead,
    ]);

    (new SendEALeadSubmittedEmailJob($lead, 'personal'))->handle();

    Mail::assertNotSent(EALeadSubmittedMail::class);
});

it('SendEACollaborateRejectedEmailJob sends to EA managers with advisors in CC', function () {
    Mail::fake();

    $advisor = TestDataSeeder::createUser(['email' => 'adv@example.com', 'name' => 'Advisor']);
    $expertAdvisor = TestDataSeeder::createUser(['email' => 'expert@example.com', 'name' => 'Expert']);
    $manager = TestDataSeeder::createUserWithRole(RolesEnum::EAManager, ['email' => 'manager@example.com', 'name' => 'Manager']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-email-003',
        'quote_type_id' => 20,
        'first_name' => 'Rej', 'last_name' => 'Test',
        'email' => 'customer2@example.com', 'mobile_no' => '0501000003',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id,
        'expert_advisor_id' => $expertAdvisor->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    (new SendEACollaborateRejectedEmailJob($lead, 'personal'))->handle();

    Mail::assertSent(EACollaborateRejectedMail::class, function ($mail) {
        return $mail->hasTo('manager@example.com')
            && $mail->hasCc('adv@example.com')
            && $mail->hasCc('expert@example.com');
    });
});

it('SendEACollaborateRejectedEmailJob skips sending when no EA managers exist', function () {
    Mail::fake();

    $advisor = TestDataSeeder::createUser(['email' => 'adv2@example.com', 'name' => 'Advisor 2']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-email-004',
        'quote_type_id' => 20,
        'first_name' => 'No', 'last_name' => 'Manager',
        'email' => 'cust3@example.com', 'mobile_no' => '0501000004',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'ea_assigned_advisor_rejected_at' => now(),
    ]);

    (new SendEACollaborateRejectedEmailJob($lead, 'personal'))->handle();

    Mail::assertNotSent(EACollaborateRejectedMail::class);
});

it('SendEAManagerDecisionEmailJob sends to both advisors with decision in subject', function () {
    Mail::fake();

    $advisor = TestDataSeeder::createUser(['email' => 'advisor3@example.com', 'name' => 'Advisor 3']);
    $expertAdvisor = TestDataSeeder::createUser(['email' => 'expert3@example.com', 'name' => 'Expert 3']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-email-005',
        'quote_type_id' => 20,
        'first_name' => 'Dec', 'last_name' => 'Test',
        'email' => 'cust4@example.com', 'mobile_no' => '0501000005',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'advisor_id' => $advisor->id,
        'expert_advisor_id' => $expertAdvisor->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    (new SendEAManagerDecisionEmailJob($lead, 'personal', 'Approved'))->handle();

    Mail::assertSent(EAManagerDecisionMail::class, function ($mail) {
        return $mail->hasTo('advisor3@example.com')
            && $mail->hasTo('expert3@example.com')
            && str_contains($mail->envelope()->subject, 'Approved');
    });
});

it('SendEAManagerDecisionEmailJob subject includes model-change decision', function () {
    Mail::fake();

    $advisor = TestDataSeeder::createUser(['email' => 'advisor4@example.com', 'name' => 'Advisor 4']);

    $lead = PersonalQuote::create([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CYB-email-006',
        'quote_type_id' => 20,
        'first_name' => 'Chg', 'last_name' => 'Test',
        'email' => 'cust5@example.com', 'mobile_no' => '0501000006',
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'referral',
        'advisor_id' => $advisor->id,
        'expert_advisor_id' => null,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    (new SendEAManagerDecisionEmailJob($lead, 'personal', 'Model Changed to Referral'))->handle();

    Mail::assertSent(EAManagerDecisionMail::class, function ($mail) {
        return str_contains($mail->envelope()->subject, 'Model Changed to Referral');
    });
});
