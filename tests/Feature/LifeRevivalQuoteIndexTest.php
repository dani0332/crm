<?php

use App\Enums\PermissionsEnum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
});

test('life revival quotes list requires authentication', function () {
    $response = $this->get(route('life-revival-quotes-list'));

    $response->assertRedirect();
});

test('life revival quotes list is forbidden without permission', function () {
    $user = TestDataSeeder::createUser();
    $this->actingAs($user);

    $response = $this->get(route('life-revival-quotes-list'));

    $response->assertForbidden();
});

test('life revival quotes list renders for user with life revival list permission', function () {
    $user = TestDataSeeder::createUser();

    $permission = Permission::firstOrCreate(
        ['name' => PermissionsEnum::LIFE_REVIVAL_QUOTES_LIST, 'guard_name' => 'web'],
        ['created_at' => now(), 'updated_at' => now()]
    );

    $user->givePermissionTo($permission);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $user->refresh();

    $this->actingAs($user);

    $response = $this->get(route('life-revival-quotes-list'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('quotes')
        ->has('formOptions')
        ->has('formOptions.currencies'));
});
