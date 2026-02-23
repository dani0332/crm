<?php

use App\Enums\AuthGuardEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

/**
 * Tests to verify authorization bypass vulnerability is fixed.
 * Previously, passing quoteTypeId in request would bypass all role checks.
 */
beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

it('prevents car advisor from accessing health payment data via quoteTypeId parameter', function () {
    $carAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    // Attempt to access Health (id=3) by passing it directly
    $healthQuoteTypeId = QuoteTypes::getId(QuoteTypes::HEALTH);
    $allowedIds = QuoteTypes::allowedIdsForUser($carAdvisor, $healthQuoteTypeId);

    // Should return empty array since user doesn't have access to Health
    expect($allowedIds)->toBe([])
        ->and($allowedIds)->not->toContain($healthQuoteTypeId);
});

it('prevents regular user from accessing any LOB data via quoteTypeId parameter', function () {
    $regularUser = TestDataSeeder::createUser();

    // Attempt to access Car (id=1) by passing it directly
    $carQuoteTypeId = QuoteTypes::getId(QuoteTypes::CAR);
    $allowedIds = QuoteTypes::allowedIdsForUser($regularUser, $carQuoteTypeId);

    // Should return empty array since user has no advisor roles
    expect($allowedIds)->toBe([])
        ->and($allowedIds)->not->toContain($carQuoteTypeId);
});

it('allows car advisor to filter their own LOB with quoteTypeId parameter', function () {
    $carAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    // Legitimate use case: filtering to their own LOB
    $carQuoteTypeId = QuoteTypes::getId(QuoteTypes::CAR);
    $allowedIds = QuoteTypes::allowedIdsForUser($carAdvisor, $carQuoteTypeId);

    // Should return the requested ID since user has access
    expect($allowedIds)->toBe([$carQuoteTypeId]);
});

it('allows admin to access any LOB via quoteTypeId parameter', function () {
    $admin = TestDataSeeder::createAdminUser();

    // Admin can access any LOB
    $healthQuoteTypeId = QuoteTypes::getId(QuoteTypes::HEALTH);
    $allowedIds = QuoteTypes::allowedIdsForUser($admin, $healthQuoteTypeId);

    // Should return the requested ID since admin has universal access
    expect($allowedIds)->toBe([$healthQuoteTypeId]);
});

it('prevents travel advisor from accessing bike payment data', function () {
    $travelAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::TravelAdvisor);

    // Attempt to access Bike (id=6)
    $bikeQuoteTypeId = QuoteTypes::getId(QuoteTypes::BIKE);
    $allowedIds = QuoteTypes::allowedIdsForUser($travelAdvisor, $bikeQuoteTypeId);

    // Should return empty array
    expect($allowedIds)->toBe([])
        ->and($allowedIds)->not->toContain($bikeQuoteTypeId);
});

it('allows multi-role advisor to access any of their authorized LOBs', function () {
    $multiRoleAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    // Assign second role to the same user (do not create a second user)
    $db = DB::connection('sqlite');
    $travelRoleId = $db->table('roles')
        ->where('name', RolesEnum::TravelAdvisor)
        ->where('guard_name', AuthGuardEnum::Web->value)
        ->value('id');
    if (! $travelRoleId) {
        $travelRoleId = $db->table('roles')->insertGetId([
            'name' => RolesEnum::TravelAdvisor,
            'guard_name' => AuthGuardEnum::Web->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    $db->table('model_has_roles')->insertOrIgnore([
        'role_id' => $travelRoleId,
        'model_type' => User::class,
        'model_id' => $multiRoleAdvisor->id,
    ]);

    $multiRoleAdvisor = $multiRoleAdvisor->fresh();

    // Should be able to access Car
    $carQuoteTypeId = QuoteTypes::getId(QuoteTypes::CAR);
    $carAllowedIds = QuoteTypes::allowedIdsForUser($multiRoleAdvisor, $carQuoteTypeId);
    expect($carAllowedIds)->toBe([$carQuoteTypeId]);

    // Should be able to access Travel
    $travelQuoteTypeId = QuoteTypes::getId(QuoteTypes::TRAVEL);
    $travelAllowedIds = QuoteTypes::allowedIdsForUser($multiRoleAdvisor, $travelQuoteTypeId);
    expect($travelAllowedIds)->toBe([$travelQuoteTypeId]);

    // Should NOT be able to access Health
    $healthQuoteTypeId = QuoteTypes::getId(QuoteTypes::HEALTH);
    $healthAllowedIds = QuoteTypes::allowedIdsForUser($multiRoleAdvisor, $healthQuoteTypeId);
    expect($healthAllowedIds)->toBe([]);
});
