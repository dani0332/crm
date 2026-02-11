<?php

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use Tests\Helpers\TestDataSeeder;

it('returns only the specified quote type id when quoteTypeId is provided', function () {
    $user = TestDataSeeder::createUser();

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

it('returns correct primary types from the enum', function () {
    $primaryTypes = QuoteTypes::primaryTypes();

    expect($primaryTypes)->toHaveCount(14)
        ->and($primaryTypes)->toContain(QuoteTypes::CAR)
        ->and($primaryTypes)->toContain(QuoteTypes::HOME)
        ->and($primaryTypes)->toContain(QuoteTypes::HEALTH)
        ->and($primaryTypes)->toContain(QuoteTypes::CYBER)
        ->and($primaryTypes)->toContain(QuoteTypes::DEVICE)
        ->and($primaryTypes)->not->toContain(QuoteTypes::AMT)
        ->and($primaryTypes)->not->toContain(QuoteTypes::PERSONAL)
        ->and($primaryTypes)->not->toContain(QuoteTypes::GROUP_MEDICAL)
        ->and($primaryTypes)->not->toContain(QuoteTypes::CORPLINE);
});
