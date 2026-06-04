<?php

declare(strict_types=1);

use App\Enums\EaQuoteStatusEligibleEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Models\TravelQuote;
use App\Services\SageApiService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->withoutMiddleware();
});

// ─── Datasets ────────────────────────────────────────────────────────────────

/**
 * All personal-quote LOB model_type values → each resolves to PersonalQuote via getQuoteObject().
 * Format: [model_type, quote_type_id]
 */
dataset('personal_quote_lobs', [
    'Bike' => ['Bike',    QuoteTypeId::Bike],
    'Cycle' => ['Cycle',   QuoteTypeId::Cycle],
    'Jetski' => ['Jetski',  QuoteTypeId::Jetski],
    'Pet' => ['Pet',     QuoteTypeId::Pet],
    'Yacht' => ['Yacht',   QuoteTypeId::Yacht],
    'Savings' => ['Savings', QuoteTypeId::Savings],
    'Home' => ['Home',    QuoteTypeId::Home],
    'Life' => ['Life',    QuoteTypeId::Life],
    'Device' => ['Device',  QuoteTypeId::Device],
    'Cyber' => ['Cyber',   QuoteTypeId::Cyber],
]);

/** Non-personal LOB model_type strings — each has its own DB table. */
dataset('non_personal_lob_types', ['Car', 'Health', 'Travel', 'Business']);

// ─── Local helpers ────────────────────────────────────────────────────────────

function makeEaPersonalQuote(array $attrs = []): PersonalQuote
{
    return PersonalQuote::create(array_merge([
        'uuid' => Str::uuid()->toString(),
        'code' => 'PQ-'.Str::upper(Str::random(6)),
        'quote_type_id' => QuoteTypeId::Cyber,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => fake()->unique()->safeEmail(),
        'mobile_no' => '050'.fake()->numerify('#######'),
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ], $attrs));
}

function makeEaCarQuote(array $attrs = []): CarQuote
{
    return CarQuote::create(array_merge([
        'uuid' => Str::uuid()->toString(),
        'code' => 'CAR-'.Str::upper(Str::random(6)),
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => fake()->unique()->safeEmail(),
        'mobile_no' => '050'.fake()->numerify('#######'),
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ], $attrs));
}

function makeEaHealthQuote(array $attrs = []): HealthQuote
{
    return HealthQuote::create(array_merge([
        'uuid' => Str::uuid()->toString(),
        'code' => 'HLT-'.Str::upper(Str::random(6)),
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => fake()->unique()->safeEmail(),
        'mobile_no' => '050'.fake()->numerify('#######'),
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ], $attrs));
}

function makeEaTravelQuote(array $attrs = []): TravelQuote
{
    return TravelQuote::create(array_merge([
        'uuid' => Str::uuid()->toString(),
        'code' => 'TRV-'.Str::upper(Str::random(6)),
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => fake()->unique()->safeEmail(),
        'mobile_no' => '050'.fake()->numerify('#######'),
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ], $attrs));
}

function makeEaBusinessQuote(array $attrs = []): BusinessQuote
{
    return BusinessQuote::create(array_merge([
        'uuid' => Str::uuid()->toString(),
        'code' => 'BUS-'.Str::upper(Str::random(6)),
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => fake()->unique()->safeEmail(),
        'mobile_no' => '050'.fake()->numerify('#######'),
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ], $attrs));
}

/**
 * Creates a quote for any non-personal LOB by model_type string.
 */
function makeEaNonPersonalQuote(string $lob, array $attrs = []): mixed
{
    return match ($lob) {
        'Car' => makeEaCarQuote($attrs),
        'Health' => makeEaHealthQuote($attrs),
        'Travel' => makeEaTravelQuote($attrs),
        'Business' => makeEaBusinessQuote($attrs),
    };
}

// ─── EA state helpers ─────────────────────────────────────────────────────────

/** All keys read by isEAQuoteStatusUpdateAllowed, with no approvals set. */
function eaCollaborateBlocked(): array
{
    return [
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'ea_manager_approved_at' => null,
        'ea_assigned_advisor_approved_at' => null,
        'ea_expert_advisor_approved_at' => null,
    ];
}

function eaCollaborateManagerApproved(): array
{
    return [
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'ea_manager_approved_at' => now(),
        'ea_assigned_advisor_approved_at' => null,
        'ea_expert_advisor_approved_at' => null,
    ];
}

function eaCollaborateBothAdvisorsApproved(): array
{
    return [
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'ea_manager_approved_at' => null,
        'ea_assigned_advisor_approved_at' => now(),
        'ea_expert_advisor_approved_at' => now(),
    ];
}

// ─── Section 1: isEAQuoteStatusUpdateAllowed helper (no DB) ──────────────────
// All objects carry every key the function reads so tests are self-documenting.

it('allows quote with non-EA source', function () {
    $quote = (object) [
        'source' => 'IMCRM',
        'ea_model' => null,
        'ea_manager_approved_at' => null,
        'ea_assigned_advisor_approved_at' => null,
        'ea_expert_advisor_approved_at' => null,
    ];

    expect(isEAQuoteStatusUpdateAllowed($quote))->toBeTrue();
});

it('allows EA_IMCRM quote when ea_model is not collaborate', function () {
    $quote = (object) [
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => null,
        'ea_manager_approved_at' => null,
        'ea_assigned_advisor_approved_at' => null,
        'ea_expert_advisor_approved_at' => null,
    ];

    expect(isEAQuoteStatusUpdateAllowed($quote))->toBeTrue();
});

it('blocks EA_IMCRM collaborate quote with no approvals', function () {
    $quote = (object) [
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'ea_manager_approved_at' => null,
        'ea_assigned_advisor_approved_at' => null,
        'ea_expert_advisor_approved_at' => null,
        'uuid' => 'BLOCK-001',
    ];

    expect(isEAQuoteStatusUpdateAllowed($quote))->toBeFalse();
});

it('allows EA_IMCRM collaborate quote when manager has approved', function () {
    $quote = (object) [
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'ea_manager_approved_at' => now()->toDateTimeString(),
        'ea_assigned_advisor_approved_at' => null,
        'ea_expert_advisor_approved_at' => null,
        'uuid' => 'ALLOW-MGR',
    ];

    expect(isEAQuoteStatusUpdateAllowed($quote))->toBeTrue();
});

it('allows EA_IMCRM collaborate quote when both advisors have approved', function () {
    $quote = (object) [
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'ea_manager_approved_at' => null,
        'ea_assigned_advisor_approved_at' => now()->toDateTimeString(),
        'ea_expert_advisor_approved_at' => now()->toDateTimeString(),
        'uuid' => 'ALLOW-BOTH',
    ];

    expect(isEAQuoteStatusUpdateAllowed($quote))->toBeTrue();
});

it('blocks EA_IMCRM collaborate quote when only assigned advisor has approved', function () {
    $quote = (object) [
        'source' => LeadSourceEnum::EA_IMCRM,
        'ea_model' => 'collaborate',
        'ea_manager_approved_at' => null,
        'ea_assigned_advisor_approved_at' => now()->toDateTimeString(),
        'ea_expert_advisor_approved_at' => null,
        'uuid' => 'BLOCK-ONE',
    ];

    expect(isEAQuoteStatusUpdateAllowed($quote))->toBeFalse();
});

// ─── Section 2: sendBookingPolicy — manual path (HTTP) ───────────────────────
// Covers every personal-quote LOB via dataset, then every non-personal LOB.

it('sendBookingPolicy blocks PolicySentToCustomer for blocked PersonalQuote EA lead', function (string $modelType, int $quoteTypeId) {
    $user = TestDataSeeder::createAdminUser();
    $quote = makeEaPersonalQuote(array_merge(eaCollaborateBlocked(), [
        'advisor_id' => $user->id,
        'quote_type_id' => $quoteTypeId,
    ]));

    $response = $this->actingAs($user)->postJson(route('send-booking-policy'), [
        'model_type' => $modelType,
        'quote_id' => $quote->id,
        'send_policy_type' => SendPolicyTypeEnum::CUSTOMER,
    ]);

    $response->assertOk();
    expect($response->json('status'))->toBeFalse();
    expect($response->json('message'))->toBe(EaQuoteStatusEligibleEnum::NotEligible->value);
    expect($quote->refresh()->quote_status_id)->not->toBe(QuoteStatusEnum::PolicySentToCustomer);
})->with('personal_quote_lobs');

it('sendBookingPolicy allows PolicySentToCustomer for manager-approved PersonalQuote EA lead', function (string $modelType, int $quoteTypeId) {
    Queue::fake();
    $user = TestDataSeeder::createAdminUser();
    $quote = makeEaPersonalQuote(array_merge(eaCollaborateManagerApproved(), [
        'advisor_id' => $user->id,
        'quote_type_id' => $quoteTypeId,
    ]));

    $response = $this->actingAs($user)->postJson(route('send-booking-policy'), [
        'model_type' => $modelType,
        'quote_id' => $quote->id,
        'send_policy_type' => SendPolicyTypeEnum::CUSTOMER,
    ]);

    $response->assertOk();
    expect($quote->refresh()->quote_status_id)->toBe(QuoteStatusEnum::PolicySentToCustomer);
    Queue::assertPushed(SendBookPolicyDocumentsJob::class);
})->with('personal_quote_lobs');

it('sendBookingPolicy allows PolicySentToCustomer when both advisors approved for PersonalQuote', function (string $modelType, int $quoteTypeId) {
    Queue::fake();
    $user = TestDataSeeder::createAdminUser();
    $quote = makeEaPersonalQuote(array_merge(eaCollaborateBothAdvisorsApproved(), [
        'advisor_id' => $user->id,
        'quote_type_id' => $quoteTypeId,
    ]));

    $response = $this->actingAs($user)->postJson(route('send-booking-policy'), [
        'model_type' => $modelType,
        'quote_id' => $quote->id,
        'send_policy_type' => SendPolicyTypeEnum::CUSTOMER,
    ]);

    $response->assertOk();
    expect($quote->refresh()->quote_status_id)->toBe(QuoteStatusEnum::PolicySentToCustomer);
})->with('personal_quote_lobs');

it('sendBookingPolicy blocks PolicySentToCustomer for blocked non-personal EA lead', function (string $lob) {
    $user = TestDataSeeder::createAdminUser();
    $quote = makeEaNonPersonalQuote($lob, array_merge(eaCollaborateBlocked(), ['advisor_id' => $user->id]));

    $response = $this->actingAs($user)->postJson(route('send-booking-policy'), [
        'model_type' => $lob,
        'quote_id' => $quote->id,
        'send_policy_type' => SendPolicyTypeEnum::CUSTOMER,
    ]);

    $response->assertOk();
    expect($response->json('status'))->toBeFalse();
    expect($response->json('message'))->toBe(EaQuoteStatusEligibleEnum::NotEligible->value);
    expect($quote->refresh()->quote_status_id)->not->toBe(QuoteStatusEnum::PolicySentToCustomer);
})->with('non_personal_lob_types');

it('sendBookingPolicy allows PolicySentToCustomer for manager-approved non-personal EA lead', function (string $lob) {
    Queue::fake();
    $user = TestDataSeeder::createAdminUser();
    $quote = makeEaNonPersonalQuote($lob, array_merge(eaCollaborateManagerApproved(), ['advisor_id' => $user->id]));

    $response = $this->actingAs($user)->postJson(route('send-booking-policy'), [
        'model_type' => $lob,
        'quote_id' => $quote->id,
        'send_policy_type' => SendPolicyTypeEnum::CUSTOMER,
    ]);

    $response->assertOk();
    expect($quote->refresh()->quote_status_id)->toBe(QuoteStatusEnum::PolicySentToCustomer);
    Queue::assertPushed(SendBookPolicyDocumentsJob::class);
})->with('non_personal_lob_types');

it('sendBookingPolicy allows non-EA PersonalQuote to proceed to PolicySentToCustomer', function () {
    Queue::fake();
    $user = TestDataSeeder::createAdminUser();
    $quote = makeEaPersonalQuote([
        'source' => 'IMCRM',
        'ea_model' => null,
        'advisor_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->postJson(route('send-booking-policy'), [
        'model_type' => 'Cyber',
        'quote_id' => $quote->id,
        'send_policy_type' => SendPolicyTypeEnum::CUSTOMER,
    ]);

    $response->assertOk();
    expect($quote->refresh()->quote_status_id)->toBe(QuoteStatusEnum::PolicySentToCustomer);
});

// ─── Section 3: postBookPolicyToSage — auto path ─────────────────────────────

it('postBookPolicyToSage blocks blocked PersonalQuote EA lead', function (string $modelType, int $quoteTypeId) {
    $quote = makeEaPersonalQuote(array_merge(eaCollaborateBlocked(), ['quote_type_id' => $quoteTypeId]));
    $result = (new SageApiService)->postBookPolicyToSage((object) [], $quote);

    expect($result['status'])->toBeFalse();
    expect($result['message'])->toBe(EaQuoteStatusEligibleEnum::NotEligible->value);
})->with('personal_quote_lobs');

it('postBookPolicyToSage allows manager-approved PersonalQuote EA lead past EA gate', function (string $modelType, int $quoteTypeId) {
    $quote = makeEaPersonalQuote(array_merge(eaCollaborateManagerApproved(), ['quote_type_id' => $quoteTypeId]));
    $result = (new SageApiService)->postBookPolicyToSage((object) [], $quote);

    // Passes EA gate — fails further in for an unrelated reason (Sage not configured in test env)
    expect($result['message'])->not->toBe(EaQuoteStatusEligibleEnum::NotEligible->value);
})->with('personal_quote_lobs');

it('postBookPolicyToSage blocks blocked non-personal EA lead', function (string $lob) {
    $quote = makeEaNonPersonalQuote($lob, eaCollaborateBlocked());
    $result = (new SageApiService)->postBookPolicyToSage((object) [], $quote);

    expect($result['status'])->toBeFalse();
    expect($result['message'])->toBe(EaQuoteStatusEligibleEnum::NotEligible->value);
})->with('non_personal_lob_types');

it('postBookPolicyToSage allows manager-approved non-personal EA lead past EA gate', function (string $lob) {
    $quote = makeEaNonPersonalQuote($lob, eaCollaborateManagerApproved());
    $result = (new SageApiService)->postBookPolicyToSage((object) [], $quote);

    expect($result['message'])->not->toBe(EaQuoteStatusEligibleEnum::NotEligible->value);
})->with('non_personal_lob_types');

// ─── Section 4: PersonalQuoteObserver ────────────────────────────────────────

it('blocked EA PersonalQuote status stays at PolicyIssued — observer never receives blocked statuses', function () {
    $user = TestDataSeeder::createAdminUser();
    $quote = makeEaPersonalQuote(array_merge(eaCollaborateBlocked(), ['advisor_id' => $user->id]));
    $originalStatus = $quote->quote_status_id;

    $this->actingAs($user)->postJson(route('send-booking-policy'), [
        'model_type' => 'Cyber',
        'quote_id' => $quote->id,
        'send_policy_type' => SendPolicyTypeEnum::CUSTOMER,
    ]);

    $quote->refresh();
    expect($quote->quote_status_id)->toBe($originalStatus);
    expect($quote->quote_status_id)->not->toBe(QuoteStatusEnum::PolicySentToCustomer);
    expect($quote->quote_status_id)->not->toBe(QuoteStatusEnum::PolicyBooked);
});

it('allowed EA PersonalQuote status update triggers observer CourtesyEmailJob dispatch', function () {
    Queue::fake();

    // quoteType relationship must resolve so PersonalQuoteObservable::handleQuoteStatusChange
    // enters the checkPersonalQuotes() branch and dispatches CourtesyEmailJob
    QuoteType::forceCreate([
        'id' => QuoteTypes::CYBER->id(),
        'code' => QuoteTypes::CYBER->value,
        'text' => 'Cyber',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = TestDataSeeder::createAdminUser();
    $quote = makeEaPersonalQuote(array_merge(eaCollaborateManagerApproved(), ['advisor_id' => $user->id]));

    $this->actingAs($user)->postJson(route('send-booking-policy'), [
        'model_type' => 'Cyber',
        'quote_id' => $quote->id,
        'send_policy_type' => SendPolicyTypeEnum::CUSTOMER,
    ]);

    expect($quote->refresh()->quote_status_id)->toBe(QuoteStatusEnum::PolicySentToCustomer);
    Queue::assertPushed(CourtesyEmailJob::class);
});
