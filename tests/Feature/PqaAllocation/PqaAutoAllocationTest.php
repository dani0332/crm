<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Models\BusinessQuote;
use App\Models\PqaLeadAllocationConfig;
use Illuminate\Testing\TestResponse;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    config([
        'constants.IMCRM_BASIC_AUTH_USER_NAME' => 'imcrm-test',
        'constants.IMCRM_BASIC_AUTH_PASSWORD' => 'imcrm-test-secret',
    ]);
});

/**
 * @param  array<string, mixed>  $data
 */
function postPqaAllocation(array $data): TestResponse
{
    return test()->withBasicAuth('imcrm-test', 'imcrm-test-secret')
        ->postJson(route('preQualificationAdvisorAllocation'), $data);
}

test('pqa allocation endpoint validates required fields', function () {
    $response = postPqaAllocation([]);

    $response->assertUnprocessable();
});

test('pqa allocation assigns pre qualification advisor to group medical lead', function () {
    $pqaUser = TestDataSeeder::createUserWithRole(RolesEnum::PreQualificationAdvisor);
    $pqaUser->forceFill(['status' => UserStatusEnum::ONLINE])->saveQuietly();

    PqaLeadAllocationConfig::factory()->create([
        'user_id' => $pqaUser->id,
        'quote_type_id' => QuoteTypes::GROUP_MEDICAL->id(),
        'allocation_count' => 0,
        'max_capacity' => 100,
        'last_allocated' => null,
    ]);

    $quote = BusinessQuote::factory()->groupMedical()->create([
        'pq_advisor_id' => null,
    ]);

    $response = postPqaAllocation([
        'quoteUUID' => $quote->uuid,
        'quoteTypeId' => QuoteTypeId::GroupMedical,
    ]);

    $response->assertSuccessful();

    dump($response->json()); // DEBUG: remove after

    expect($quote->fresh()->pq_advisor_id)->toBe($pqaUser->id);

    $config = PqaLeadAllocationConfig::query()
        ->where('user_id', $pqaUser->id)
        ->where('quote_type_id', QuoteTypes::GROUP_MEDICAL->id())
        ->first();

    expect($config)->not->toBeNull()
        ->and((int) $config->auto_assignment_count)->toBe(1)
        ->and((int) $config->allocation_count)->toBe(1);
});

test('pqa allocation skips lead that already has a pre qualification advisor', function () {
    $existingPqa = TestDataSeeder::createUserWithRole(RolesEnum::PreQualificationAdvisor);

    $quote = BusinessQuote::factory()->groupMedical()->create([
        'pq_advisor_id' => $existingPqa->id,
    ]);

    $response = postPqaAllocation([
        'quoteUUID' => $quote->uuid,
        'quoteTypeId' => QuoteTypeId::GroupMedical,
    ]);

    $response->assertSuccessful();
    expect($quote->fresh()->pq_advisor_id)->toBe($existingPqa->id);
});

test('pqa allocation returns not found when no advisor is available', function () {
    $quote = BusinessQuote::factory()->groupMedical()->create([
        'pq_advisor_id' => null,
    ]);

    $response = postPqaAllocation([
        'quoteUUID' => $quote->uuid,
        'quoteTypeId' => QuoteTypeId::GroupMedical,
    ]);

    $response->assertSuccessful();
    expect($quote->fresh()->pq_advisor_id)->toBeNull();
    expect($response->json('data.assignedPqaAdvisorId'))->toBe(0);
});

test('pqa allocation accepts reAssignAdvisor alias like assign-quote', function () {
    $pqaUser = TestDataSeeder::createUserWithRole(RolesEnum::PreQualificationAdvisor);
    $pqaUser->forceFill(['status' => UserStatusEnum::ONLINE])->saveQuietly();

    PqaLeadAllocationConfig::factory()->create([
        'user_id' => $pqaUser->id,
        'quote_type_id' => QuoteTypes::BUSINESS->id(),
        'allocation_count' => 0,
        'max_capacity' => 100,
        'last_allocated' => null,
    ]);

    $quote = BusinessQuote::factory()->groupMedical()->create([
        'pq_advisor_id' => null,
    ]);

    $response = postPqaAllocation([
        'quoteUUID' => $quote->uuid,
        'quoteTypeId' => QuoteTypeId::Business,
        'reAssignAdvisor' => true,
    ]);

    $response->assertSuccessful();
    expect($quote->fresh()->pq_advisor_id)->toBe($pqaUser->id);
});

test('pqa allocation resolves business quote by BUS- code suffix', function () {
    $pqaUser = TestDataSeeder::createUserWithRole(RolesEnum::PreQualificationAdvisor);
    $pqaUser->forceFill(['status' => UserStatusEnum::ONLINE])->saveQuietly();

    PqaLeadAllocationConfig::factory()->create([
        'user_id' => $pqaUser->id,
        'quote_type_id' => QuoteTypes::BUSINESS->id(),
        'allocation_count' => 0,
        'max_capacity' => 100,
        'last_allocated' => null,
    ]);

    $quote = BusinessQuote::factory()->groupMedical()->create([
        'pq_advisor_id' => null,
        'code' => 'BUS-ABCDEF12',
    ]);

    $suffix = substr((string) $quote->code, 4);

    $response = postPqaAllocation([
        'quoteUUID' => $suffix,
        'quoteTypeId' => QuoteTypeId::Business,
    ]);

    $response->assertSuccessful();
    expect($quote->fresh()->pq_advisor_id)->toBe($pqaUser->id);
});

test('pqa allocation assigns pre qualification advisor to corpline lead', function () {
    $pqaUser = TestDataSeeder::createUserWithRole(RolesEnum::PreQualificationAdvisor);
    $pqaUser->forceFill(['status' => UserStatusEnum::ONLINE])->saveQuietly();

    PqaLeadAllocationConfig::factory()->create([
        'user_id' => $pqaUser->id,
        'quote_type_id' => QuoteTypes::CORPLINE->id(),
        'allocation_count' => 0,
        'max_capacity' => 100,
        'last_allocated' => null,
    ]);

    $quote = BusinessQuote::factory()->corpline()->create([
        'pq_advisor_id' => null,
    ]);

    $response = postPqaAllocation([
        'quoteUUID' => $quote->uuid,
        'quoteTypeId' => QuoteTypeId::Corpline,
    ]);

    $response->assertSuccessful();

    expect($quote->fresh()->pq_advisor_id)->toBe($pqaUser->id);

    $config = PqaLeadAllocationConfig::query()
        ->where('user_id', $pqaUser->id)
        ->where('quote_type_id', QuoteTypes::CORPLINE->id())
        ->first();

    expect($config)->not->toBeNull()
        ->and((int) $config->auto_assignment_count)->toBe(1)
        ->and((int) $config->allocation_count)->toBe(1);
});

test('pqa allocation returns not found for corpline when no corpline advisor is configured', function () {
    $pqaUser = TestDataSeeder::createUserWithRole(RolesEnum::PreQualificationAdvisor);
    $pqaUser->forceFill(['status' => UserStatusEnum::ONLINE])->saveQuietly();

    // Advisor has a Group Medical config but not a Corpline config
    PqaLeadAllocationConfig::factory()->create([
        'user_id' => $pqaUser->id,
        'quote_type_id' => QuoteTypes::GROUP_MEDICAL->id(),
        'allocation_count' => 0,
        'max_capacity' => 100,
    ]);

    $quote = BusinessQuote::factory()->corpline()->create([
        'pq_advisor_id' => null,
    ]);

    $response = postPqaAllocation([
        'quoteUUID' => $quote->uuid,
        'quoteTypeId' => QuoteTypeId::Corpline,
    ]);

    $response->assertSuccessful();
    expect($quote->fresh()->pq_advisor_id)->toBeNull();
    expect($response->json('data.assignedPqaAdvisorId'))->toBe(0);
});

test('pqa allocation corpline and group medical advisors are allocated from separate pools', function () {
    $gmAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::PreQualificationAdvisor);
    $gmAdvisor->forceFill(['status' => UserStatusEnum::ONLINE])->saveQuietly();

    $corplineAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::PreQualificationAdvisor);
    $corplineAdvisor->forceFill(['status' => UserStatusEnum::ONLINE])->saveQuietly();

    PqaLeadAllocationConfig::factory()->create([
        'user_id' => $gmAdvisor->id,
        'quote_type_id' => QuoteTypes::GROUP_MEDICAL->id(),
        'allocation_count' => 0,
        'max_capacity' => 100,
    ]);

    PqaLeadAllocationConfig::factory()->create([
        'user_id' => $corplineAdvisor->id,
        'quote_type_id' => QuoteTypes::CORPLINE->id(),
        'allocation_count' => 0,
        'max_capacity' => 100,
    ]);

    $gmQuote = BusinessQuote::factory()->groupMedical()->create(['pq_advisor_id' => null]);
    $corplineQuote = BusinessQuote::factory()->corpline()->create(['pq_advisor_id' => null]);

    postPqaAllocation(['quoteUUID' => $gmQuote->uuid, 'quoteTypeId' => QuoteTypeId::GroupMedical])->assertSuccessful();
    postPqaAllocation(['quoteUUID' => $corplineQuote->uuid, 'quoteTypeId' => QuoteTypeId::Corpline])->assertSuccessful();

    expect($gmQuote->fresh()->pq_advisor_id)->toBe($gmAdvisor->id);
    expect($corplineQuote->fresh()->pq_advisor_id)->toBe($corplineAdvisor->id);
});
