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
use App\Services\BirdService;
use Illuminate\Support\Str;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

it('SendEALeadSubmittedEmailJob sends Bird webhook with advisor and lead generator payload', function () {
    $birdService = $this->mock(BirdService::class);

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

    $birdService->shouldReceive('triggerWebHookRequest')
        ->once()
        ->with('https://test-ea-lead-submitted', Mockery::on(function ($payload) use ($advisor, $leadGen) {
            return $payload['advisor_email'] === $advisor->email
                && $payload['lead_generator_email'] === $leadGen->email
                && in_array('manager@example.com', $payload['manager_emails']);
        }));

    (new SendEALeadSubmittedEmailJob($lead, 'personal', 'https://test-ea-lead-submitted'))->handle();
});

it('SendEALeadSubmittedEmailJob does not send if advisor has no email', function () {
    $birdService = $this->mock(BirdService::class);
    $birdService->shouldReceive('triggerWebHookRequest')->never();

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

    (new SendEALeadSubmittedEmailJob($lead, 'personal', 'https://test-ea-lead-submitted'))->handle();
});

it('SendEACollaborateRejectedEmailJob sends Bird webhook to EA managers with advisor details', function () {
    $birdService = $this->mock(BirdService::class);

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

    $birdService->shouldReceive('triggerWebHookRequest')
        ->once()
        ->with('https://test-ea-collaborate-rejected', Mockery::on(function ($payload) {
            return in_array('manager@example.com', $payload['manager_emails'])
                && $payload['advisor_email'] === 'adv@example.com'
                && $payload['expert_advisor_email'] === 'expert@example.com';
        }));

    (new SendEACollaborateRejectedEmailJob($lead, 'personal', 'https://test-ea-collaborate-rejected'))->handle();
});

it('SendEACollaborateRejectedEmailJob skips sending when no EA managers exist', function () {
    $birdService = $this->mock(BirdService::class);
    $birdService->shouldReceive('triggerWebHookRequest')->never();

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

    (new SendEACollaborateRejectedEmailJob($lead, 'personal', 'https://test-ea-collaborate-rejected'))->handle();
});

it('SendEAManagerDecisionEmailJob sends Bird webhook to both advisors with decision', function () {
    $birdService = $this->mock(BirdService::class);

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

    $birdService->shouldReceive('triggerWebHookRequest')
        ->once()
        ->with('https://test-ea-manager-decision', Mockery::on(function ($payload) {
            return $payload['decision'] === 'Approved'
                && in_array('advisor3@example.com', $payload['recipient_emails'])
                && in_array('expert3@example.com', $payload['recipient_emails']);
        }));

    (new SendEAManagerDecisionEmailJob($lead, 'personal', 'Approved', 'https://test-ea-manager-decision'))->handle();
});

it('SendEAManagerDecisionEmailJob sends with model-change decision', function () {
    $birdService = $this->mock(BirdService::class);

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

    $birdService->shouldReceive('triggerWebHookRequest')
        ->once()
        ->with('https://test-ea-manager-decision', Mockery::on(function ($payload) {
            return $payload['decision'] === 'Model Changed to Referral';
        }));

    (new SendEAManagerDecisionEmailJob($lead, 'personal', 'Model Changed to Referral', 'https://test-ea-manager-decision'))->handle();
});
