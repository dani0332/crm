<?php

use App\Enums\PermissionsEnum;
use App\Models\User;
use App\Services\UserService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

describe('UserController - Update Active State', function () {

    test('it updates user active state successfully', function () {
        $authUser = User::factory()->create();

        $permission = Permission::firstOrCreate(
            ['name' => PermissionsEnum::UsersEdit, 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()]
        );
        $authUser->givePermissionTo($permission);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $authUser->refresh();

        $this->actingAs($authUser);

        $user = User::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->postJson('/admin/update-user-state', [
            'id' => $user->id,
            'status' => true,
        ]);

        // Assert response
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'User status updated successfully',
            ]);

        // Assert database updated
        expect($user->fresh()->is_active)->toBe(1);
    });

    test('it rolls back user active state when sendManagerDeactivationEmail throws', function () {
        $authUser = User::factory()->create();

        $permission = Permission::firstOrCreate(
            ['name' => PermissionsEnum::UsersEdit, 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()]
        );
        $authUser->givePermissionTo($permission);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $authUser->refresh();

        $this->actingAs($authUser);

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->mock(UserService::class, function ($mock) {
            $mock->shouldReceive('sendManagerDeactivationEmail')
                ->once()
                ->andThrow(new RuntimeException('Simulated getSubordinates failure'));
        });

        $response = $this->postJson('/admin/update-user-state', [
            'id' => $user->id,
            'status' => false,
        ]);

        $response->assertStatus(500);
        expect($user->fresh()->is_active)->toBe(1);
    });

});
