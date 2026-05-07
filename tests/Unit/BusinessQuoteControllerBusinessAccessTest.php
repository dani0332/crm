<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Http\Controllers\BusinessQuoteController;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

it('denies business show access for a user with only unrelated advisor role and no business permissions', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    $this->actingAs($user);

    expect(app(BusinessQuoteController::class)->checkUserHasBusinessQuoteAccess('show'))->toBeFalse();
});

it('allows business show access for business advisor role', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::BusinessAdvisor);

    $this->actingAs($user);

    expect(app(BusinessQuoteController::class)->checkUserHasBusinessQuoteAccess('show'))->toBeTrue();
});

it('allows business show access for business manager role', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::BusinessManager);

    $this->actingAs($user);

    expect(app(BusinessQuoteController::class)->checkUserHasBusinessQuoteAccess('show'))->toBeTrue();
});

it('allows business show access for corpline advisor role', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::CorpLineAdvisor);

    $this->actingAs($user);

    expect(app(BusinessQuoteController::class)->checkUserHasBusinessQuoteAccess('show'))->toBeTrue();
});

it('allows business show access for corpline manager role', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::CorplineManager);

    $this->actingAs($user);

    expect(app(BusinessQuoteController::class)->checkUserHasBusinessQuoteAccess('show'))->toBeTrue();
});

it('allows business show access for corpline renewal manager role', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::CorplineRenewalManager);

    $this->actingAs($user);

    expect(app(BusinessQuoteController::class)->checkUserHasBusinessQuoteAccess('show'))->toBeTrue();
});

it('allows business show access when user has business quotes edit on an existing advisor role', function () {
    TestDataSeeder::seedRolePermissions(RolesEnum::CarAdvisor, [PermissionsEnum::BusinessQuotesEdit]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    $this->actingAs($user);

    expect(app(BusinessQuoteController::class)->checkUserHasBusinessQuoteAccess('show'))->toBeTrue();
});

it('allows business show access when user has business quotes create on an existing advisor role', function () {
    TestDataSeeder::seedRolePermissions(RolesEnum::CarAdvisor, [PermissionsEnum::BusinessQuotesCreate]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    $this->actingAs($user);

    expect(app(BusinessQuoteController::class)->checkUserHasBusinessQuoteAccess('show'))->toBeTrue();
});

it('allows business show access when user has only business quotes list permission', function () {
    TestDataSeeder::seedRolePermissions(RolesEnum::CarAdvisor, [PermissionsEnum::BusinessQuotesList]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    $this->actingAs($user);

    expect(app(BusinessQuoteController::class)->checkUserHasBusinessQuoteAccess('show'))->toBeTrue();
});

it('allows business show access when user has corpline quotes edit permission', function () {
    TestDataSeeder::seedRolePermissions(RolesEnum::CarAdvisor, [PermissionsEnum::CorpLineQuotesEdit]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    $this->actingAs($user);

    expect(app(BusinessQuoteController::class)->checkUserHasBusinessQuoteAccess('show'))->toBeTrue();
});

it('allows business show access when user has corpline quotes create permission', function () {
    TestDataSeeder::seedRolePermissions(RolesEnum::CarAdvisor, [PermissionsEnum::CorpLineQuotesCreate]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    $this->actingAs($user);

    expect(app(BusinessQuoteController::class)->checkUserHasBusinessQuoteAccess('show'))->toBeTrue();
});

it('denies business show access when user has only corpline quotes list permission', function () {
    TestDataSeeder::seedRolePermissions(RolesEnum::CarAdvisor, [PermissionsEnum::CorpLineQuotesList]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    $this->actingAs($user);

    expect(app(BusinessQuoteController::class)->checkUserHasBusinessQuoteAccess('show'))->toBeFalse();
});

it('allows business show access when user has view all leads permission', function () {
    TestDataSeeder::seedRolePermissions(RolesEnum::CarAdvisor, [PermissionsEnum::VIEW_ALL_LEADS]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    $this->actingAs($user);

    expect(app(BusinessQuoteController::class)->checkUserHasBusinessQuoteAccess('show'))->toBeTrue();
});

it('allows business show access for admin role', function () {
    $user = TestDataSeeder::createAdminUser();

    $this->actingAs($user);

    expect(app(BusinessQuoteController::class)->checkUserHasBusinessQuoteAccess('show'))->toBeTrue();
});

it('allows business show access for engineering role', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::Engineering);

    $this->actingAs($user);

    expect(app(BusinessQuoteController::class)->checkUserHasBusinessQuoteAccess('show'))->toBeTrue();
});
