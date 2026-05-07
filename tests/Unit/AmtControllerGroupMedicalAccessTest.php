<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Http\Controllers\V2\AmtController;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

it('denies group medical show access for a user with only unrelated advisor role and no gm permissions', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::BusinessAdvisor);

    $this->actingAs($user);

    expect(app(AmtController::class)->checkUserHasGroupMedicalAccess('show'))->toBeFalse();
});

it('allows group medical show access for gm advisor role', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::GMAdvisor);

    $this->actingAs($user);

    expect(app(AmtController::class)->checkUserHasGroupMedicalAccess('show'))->toBeTrue();
});

it('allows group medical show access for gm manager role', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::GMManager);

    $this->actingAs($user);

    expect(app(AmtController::class)->checkUserHasGroupMedicalAccess('show'))->toBeTrue();
});

it('allows group medical show access for gm deputy manager role', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::GMDeputyManager);

    $this->actingAs($user);

    expect(app(AmtController::class)->checkUserHasGroupMedicalAccess('show'))->toBeTrue();
});

it('allows group medical show access when user has gm quotes edit on an existing advisor role', function () {
    TestDataSeeder::seedRolePermissions(RolesEnum::CarAdvisor, [PermissionsEnum::GMQuotesEdit]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    $this->actingAs($user);

    expect(app(AmtController::class)->checkUserHasGroupMedicalAccess('show'))->toBeTrue();
});

it('allows group medical show access when user has only gm quotes list permission', function () {
    TestDataSeeder::seedRolePermissions(RolesEnum::CarAdvisor, [PermissionsEnum::GMQuotesList]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    $this->actingAs($user);

    expect(app(AmtController::class)->checkUserHasGroupMedicalAccess('show'))->toBeTrue();
});

it('allows group medical show access when user has view all leads permission', function () {
    TestDataSeeder::seedRolePermissions(RolesEnum::CarAdvisor, [PermissionsEnum::VIEW_ALL_LEADS]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);

    $this->actingAs($user);

    expect(app(AmtController::class)->checkUserHasGroupMedicalAccess('show'))->toBeTrue();
});

it('allows group medical show access for admin role', function () {
    $user = TestDataSeeder::createAdminUser();

    $this->actingAs($user);

    expect(app(AmtController::class)->checkUserHasGroupMedicalAccess('show'))->toBeTrue();
});

it('allows group medical show access for engineering role', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::Engineering);

    $this->actingAs($user);

    expect(app(AmtController::class)->checkUserHasGroupMedicalAccess('show'))->toBeTrue();
});
