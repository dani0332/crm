<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\BusinessQuote;
use App\Models\PqaLeadAllocationConfig;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    TestDataSeeder::seedRolePermissions(RolesEnum::PreQualificationLead, [
        PermissionsEnum::ASSIGN_GROUP_MEDICAL_PRE_QUALIFICATION_ADVISOR,
    ]);
});

test('guests cannot assign a pre-qualification advisor', function () {
    $this->post(route('assign-pre-qualification-advisor'), [
        'assigned_lead_id' => '1',
        'pq_advisor_id' => 1,
        'modelType' => 'business',
    ])->assertRedirect();
});

test('users without permission cannot assign a pre-qualification advisor', function () {
    $user = TestDataSeeder::createUser();
    $this->actingAs($user);

    $this->post(route('assign-pre-qualification-advisor'), [
        'assigned_lead_id' => '1',
        'pq_advisor_id' => 1,
        'modelType' => 'business',
    ])->assertForbidden();
});

test('pre-qualification lead can assign pqa to a group medical business quote', function () {
    $actor = TestDataSeeder::createUserWithRole(RolesEnum::PreQualificationLead);
    $pqaUser = TestDataSeeder::createUserWithRole(RolesEnum::PreQualificationAdvisor);

    PqaLeadAllocationConfig::factory()->create([
        'user_id' => $pqaUser->id,
        'quote_type_id' => QuoteTypes::BUSINESS->id(),
    ]);

    $quote = BusinessQuote::factory()->groupMedical()->create();

    $this->actingAs($actor);

    $this->post(route('assign-pre-qualification-advisor'), [
        'assigned_lead_id' => (string) $quote->id,
        'pq_advisor_id' => $pqaUser->id,
        'modelType' => 'business',
    ])->assertRedirect();

    expect($quote->fresh()->pq_advisor_id)->toBe($pqaUser->id);

    $config = PqaLeadAllocationConfig::query()
        ->where('user_id', $pqaUser->id)
        ->where('quote_type_id', QuoteTypes::BUSINESS->id())
        ->first();

    expect($config)->not->toBeNull()
        ->and((int) $config->manual_assignment_count)->toBe(1)
        ->and((int) $config->allocation_count)->toBe(1);
});

test('admin can assign pqa without the pre-qualification lead role', function () {
    $admin = TestDataSeeder::createAdminUser();
    $pqaUser = TestDataSeeder::createUserWithRole(RolesEnum::PreQualificationAdvisor);

    PqaLeadAllocationConfig::factory()->create([
        'user_id' => $pqaUser->id,
        'quote_type_id' => QuoteTypes::BUSINESS->id(),
    ]);

    $quote = BusinessQuote::factory()->groupMedical()->create();

    $this->actingAs($admin);

    $this->post(route('assign-pre-qualification-advisor'), [
        'assigned_lead_id' => (string) $quote->id,
        'pq_advisor_id' => $pqaUser->id,
        'modelType' => 'business',
    ])->assertRedirect();

    expect($quote->fresh()->pq_advisor_id)->toBe($pqaUser->id);
});
