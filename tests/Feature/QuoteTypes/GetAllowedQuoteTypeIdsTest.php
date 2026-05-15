<?php

use App\Enums\AuthGuardEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

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

    // Assign second role to the same user
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
        'model_id' => $user->id,
    ]);

    $user = $user->fresh();

    $result = QuoteTypes::allowedIdsForUser($user);

    expect($result)
        ->toContain(QuoteTypes::getId(QuoteTypes::CAR))
        ->toContain(QuoteTypes::getId(QuoteTypes::TRAVEL));
});

it('correctly checks access using advisorRoles method instead of string concatenation', function () {
    // Test that HEALTH advisor role is properly recognized
    // HEALTH has multiple advisor roles: HealthAdvisor, EBPAdvisor, RMAdvisor
    $healthAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::HealthAdvisor, ['email' => 'health@test.com']);
    expect(QuoteTypes::HEALTH->userHasAccess($healthAdvisor))->toBeTrue();

    $ebpAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::EBPAdvisor, ['email' => 'ebp@test.com']);
    expect(QuoteTypes::HEALTH->userHasAccess($ebpAdvisor))->toBeTrue();

    $rmAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::RMAdvisor, ['email' => 'rm@test.com']);
    expect(QuoteTypes::HEALTH->userHasAccess($rmAdvisor))->toBeTrue();

    // Test CAR_REVIVAL advisor role (uses CarRevivalAdvisor, not CAR_REVIVAL_ADVISOR)
    $carRevivalAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::CarRevivalAdvisor, ['email' => 'carrevival@test.com']);
    expect(QuoteTypes::CAR_REVIVAL->userHasAccess($carRevivalAdvisor))->toBeTrue();

    // Test BUSINESS quote type (maps to multiple advisor roles)
    $businessAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::BusinessAdvisor, ['email' => 'business@test.com']);
    expect(QuoteTypes::BUSINESS->userHasAccess($businessAdvisor))->toBeTrue();

    $corplineAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::CorpLineAdvisor, ['email' => 'corpline@test.com']);
    expect(QuoteTypes::BUSINESS->userHasAccess($corplineAdvisor))->toBeTrue();

    $gmAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::GMAdvisor, ['email' => 'gm@test.com']);
    expect(QuoteTypes::BUSINESS->userHasAccess($gmAdvisor))->toBeTrue();

    // Test JETSKI advisor role
    $jetskiAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::JetskiAdvisor, ['email' => 'jetski@test.com']);
    expect(QuoteTypes::JETSKI->userHasAccess($jetskiAdvisor))->toBeTrue();

    $smartPhoneAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::SmartPhoneAdvisor, ['email' => 'device@test.com']);
    expect(QuoteTypes::DEVICE->userHasAccess($smartPhoneAdvisor))->toBeTrue();

    $smartPhoneAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::SmartPhoneAdvisor, ['email' => 'smartphone-advisor@test.com']);
    expect(QuoteTypes::DEVICE->userHasAccess($smartPhoneAdvisor))->toBeTrue();
});

it('correctly checks manager roles for quote type access', function () {
    $carManager = TestDataSeeder::createUserWithRole(RolesEnum::CarManager, ['email' => 'carmanager@test.com']);
    expect(QuoteTypes::CAR->userHasAccess($carManager))->toBeTrue();

    $healthManager = TestDataSeeder::createUserWithRole(RolesEnum::HealthManager, ['email' => 'healthmanager@test.com']);
    expect(QuoteTypes::HEALTH->userHasAccess($healthManager))->toBeTrue();

    $homeManager = TestDataSeeder::createUserWithRole(RolesEnum::HomeManager, ['email' => 'homemanager@test.com']);
    expect(QuoteTypes::HOME->userHasAccess($homeManager))->toBeTrue();
});

it('returns correct primary types from the enum', function () {
    $primaryTypes = QuoteTypes::primaryTypes();

    expect($primaryTypes)->toHaveCount(13)
        ->and($primaryTypes)->toContain(QuoteTypes::CAR)
        ->and($primaryTypes)->toContain(QuoteTypes::HOME)
        ->and($primaryTypes)->toContain(QuoteTypes::HEALTH)
        ->and($primaryTypes)->toContain(QuoteTypes::SAVINGS)
        ->and($primaryTypes)->toContain(QuoteTypes::CYBER)
        ->and($primaryTypes)->not->toContain(QuoteTypes::AMT)
        ->and($primaryTypes)->not->toContain(QuoteTypes::PERSONAL)
        ->and($primaryTypes)->not->toContain(QuoteTypes::GROUP_MEDICAL)
        ->and($primaryTypes)->not->toContain(QuoteTypes::CORPLINE);
});
