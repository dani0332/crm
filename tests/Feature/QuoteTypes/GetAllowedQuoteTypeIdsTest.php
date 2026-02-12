<?php

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use Tests\Helpers\TestDataSeeder;

it('returns only the specified quote type id when quoteTypeId is provided and user has access', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::HealthAdvisor);

    $result = QuoteTypes::allowedIdsForUser($user, 3);

    expect($result)->toBe([3]);
});

it('returns empty array when quoteTypeId is provided but user lacks access', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    // User only has CAR_ADVISOR role, attempting to access HEALTH (id=3)
    $result = QuoteTypes::allowedIdsForUser($user, 3);

    expect($result)->toBe([]);
});

it('returns the specified quote type id when admin requests specific quote type', function () {
    $user = TestDataSeeder::createAdminUser();

    $result = QuoteTypes::allowedIdsForUser($user, 3);

    expect($result)->toBe([3]);
});

it('returns all primary quote type ids for admin users', function () {
    $user = TestDataSeeder::createAdminUser();

    $result = QuoteTypes::allowedIdsForUser($user);

    $expectedIds = collect(QuoteTypes::primaryTypes())
        ->map(fn (QuoteTypes $qt) => QuoteTypes::getId($qt))
        ->filter()
        ->values()
        ->all();

    expect($result)->toBe($expectedIds);
});

it('returns only quote type ids matching user advisor role', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    $result = QuoteTypes::allowedIdsForUser($user);

    expect($result)->toContain(QuoteTypes::getId(QuoteTypes::CAR))
        ->and(count($result))->toBe(1);
});

it('returns only quote type ids matching user manager role', function () {
    $user = TestDataSeeder::createUserWithRole('CAR_MANAGER');

    $result = QuoteTypes::allowedIdsForUser($user);

    expect($result)->toContain(QuoteTypes::getId(QuoteTypes::CAR))
        ->and(count($result))->toBe(1);
});

it('returns empty array for user with no matching roles', function () {
    $user = TestDataSeeder::createUser();

    $result = QuoteTypes::allowedIdsForUser($user);

    expect($result)->toBe([]);
});

it('returns quote type ids for user with multiple advisor roles', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);
    TestDataSeeder::createUserWithRole(RolesEnum::TravelAdvisor, ['id' => $user->id, 'email' => $user->email]);

    $user = $user->fresh();

    $result = QuoteTypes::allowedIdsForUser($user);

    expect($result)
        ->toContain(QuoteTypes::getId(QuoteTypes::CAR))
        ->toContain(QuoteTypes::getId(QuoteTypes::TRAVEL));
});

it('correctly checks access using advisorRoles method instead of string concatenation', function () {
    // Test that HEALTH advisor role is properly recognized
    // HEALTH has multiple advisor roles: HealthAdvisor, EBPAdvisor, RMAdvisor
    $healthAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::HealthAdvisor);
    expect(QuoteTypes::HEALTH->userHasAccess($healthAdvisor))->toBeTrue();

    $ebpAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::EBPAdvisor);
    expect(QuoteTypes::HEALTH->userHasAccess($ebpAdvisor))->toBeTrue();

    $rmAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::RMAdvisor);
    expect(QuoteTypes::HEALTH->userHasAccess($rmAdvisor))->toBeTrue();

    // Test CAR_REVIVAL advisor role (uses CarRevivalAdvisor, not CAR_REVIVAL_ADVISOR)
    $carRevivalAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::CarRevivalAdvisor);
    expect(QuoteTypes::CAR_REVIVAL->userHasAccess($carRevivalAdvisor))->toBeTrue();

    // Test BUSINESS quote type (maps to multiple advisor roles)
    $corplineAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::CorpLineAdvisor);
    expect(QuoteTypes::BUSINESS->userHasAccess($corplineAdvisor))->toBeTrue();

    $gmAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::GMAdvisor);
    expect(QuoteTypes::BUSINESS->userHasAccess($gmAdvisor))->toBeTrue();
});

it('correctly checks manager roles for quote type access', function () {
    $carManager = TestDataSeeder::createUserWithRole(RolesEnum::CarManager);
    expect(QuoteTypes::CAR->userHasAccess($carManager))->toBeTrue();

    $healthManager = TestDataSeeder::createUserWithRole(RolesEnum::HealthManager);
    expect(QuoteTypes::HEALTH->userHasAccess($healthManager))->toBeTrue();

    $homeManager = TestDataSeeder::createUserWithRole(RolesEnum::HomeManager);
    expect(QuoteTypes::HOME->userHasAccess($homeManager))->toBeTrue();
});

it('returns correct primary types from the enum', function () {
    $primaryTypes = QuoteTypes::primaryTypes();

    expect($primaryTypes)->toHaveCount(12)
        ->and($primaryTypes)->toContain(QuoteTypes::CAR)
        ->and($primaryTypes)->toContain(QuoteTypes::HOME)
        ->and($primaryTypes)->toContain(QuoteTypes::HEALTH)
        ->and($primaryTypes)->toContain(QuoteTypes::SAVINGS)
        ->and($primaryTypes)->not->toContain(QuoteTypes::AMT)
        ->and($primaryTypes)->not->toContain(QuoteTypes::PERSONAL)
        ->and($primaryTypes)->not->toContain(QuoteTypes::GROUP_MEDICAL)
        ->and($primaryTypes)->not->toContain(QuoteTypes::CORPLINE);
});
