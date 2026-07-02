<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Enums\UserStatusEnum;
use App\Models\BusinessQuote;
use App\Models\HealthQuote;
use App\Models\PqaLeadAllocationConfig;
use App\Models\QuoteType;
use App\Models\Team;
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

test('pqa allocation assigns pre qualification advisor to health lead', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::PreQualificationAdvisor);
    $advisor->forceFill(['status' => UserStatusEnum::ONLINE])->saveQuietly();

    PqaLeadAllocationConfig::factory()->create([
        'user_id' => $advisor->id,
        'quote_type_id' => QuoteTypeId::Health,
        'allocation_count' => 0,
        'max_capacity' => 100,
    ]);

    QuoteType::factory()->createHealthForSqlite();

    $team = Team::forceCreate(['name' => QuoteTypes::HEALTH->value, 'type' => TeamTypeEnum::PRODUCT, 'is_active' => 1]);
    $advisor->products()->attach($team->id);

    $quote = HealthQuote::factory()->create(['pq_advisor_id' => null]);

    $response = postPqaAllocation([
        'quoteUUID' => $quote->uuid,
        'quoteTypeId' => QuoteTypeId::Health,
    ]);

    $response->assertSuccessful();
    expect($quote->fresh()->pq_advisor_id)->toBe($advisor->id);
});
