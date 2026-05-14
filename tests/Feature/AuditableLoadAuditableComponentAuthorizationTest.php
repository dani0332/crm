<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Http\Middleware\CheckLastLoginMiddleware;
use App\Http\Middleware\PreventRequestForgery;
use App\Models\User;
use Laravel\Telescope\Telescope;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    if (class_exists(Telescope::class)) {
        Telescope::stopRecording();
    }

    TestSchemaCreator::createMinimalSchema();

    $this->withoutMiddleware([
        PreventRequestForgery::class,
        CheckLastLoginMiddleware::class,
    ]);
});

test('post auditable is forbidden without ila-config-all-lob permission', function () {
    $user = User::factory()->create([
        'last_login' => now(),
    ]);

    $this->actingAs($user);

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->post('/auditable', [
        'auditableId' => 1,
        'auditableType' => 'App\Models\Payment',
        'jsonData' => true,
    ]);

    $response->assertForbidden();
});

test('post auditable returns json when user has ila-config-all-lob permission and jsonData is set', function () {
    $user = User::factory()->create([
        'last_login' => now(),
    ]);

    $permission = Permission::firstOrCreate(
        ['name' => PermissionsEnum::ILA_CONFIG_ALL_LOB, 'guard_name' => 'web'],
        ['created_at' => now(), 'updated_at' => now()]
    );
    $user->givePermissionTo($permission);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $user->refresh();

    $this->actingAs($user);

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->post('/auditable', [
        'auditableId' => 999,
        'auditableType' => 'App\Models\Payment',
        'jsonData' => true,
    ]);

    $response->assertOk()
        ->assertJson([]);
});
