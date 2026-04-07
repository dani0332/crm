<?php

use App\Enums\RolesEnum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;
use Tests\TestCase;

/**
 * @var TestCase $this
 *
 * @method \Illuminate\Testing\TestResponse get(string $uri, array $headers = [])
 * @method void actingAs($user, $guard = null)
 *
 * @extends TestCase
 */
beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

test('transapp home is forbidden for user without transapp-search permission', function () {
    $user = TestDataSeeder::createUser();
    $this->actingAs($user);

    $response = $this->get('/transapp/home');

    $response->assertStatus(403);
});

test('transapp home is accessible for role with transapp-search permission', function () {
    /** @var TestCase $this */
    $user = TestDataSeeder::createUserWithRole(RolesEnum::Engineering);

    // seed only the transapp-search permission for the Engineering role
    TestDataSeeder::seedRolePermissions(RolesEnum::Engineering, ['transapp-search']);

    $this->actingAs($user);

    $response = $this->get('/transapp/home');

    $response->assertStatus(200);
});

test('transapp home is accessible when permission is directly assigned to user', function () {
    $user = TestDataSeeder::createUser();

    // Create permission and assign directly to user
    $permission = Permission::firstOrCreate(
        ['name' => 'transapp-search', 'guard_name' => 'web'],
        ['created_at' => now(), 'updated_at' => now()]
    );

    $user->givePermissionTo($permission);

    // Clear Spatie permission cache and refresh user
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $user->refresh();

    $this->actingAs($user);

    $response = $this->get('/transapp/home');

    $response->assertStatus(200);
});
