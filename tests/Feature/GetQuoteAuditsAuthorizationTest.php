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

function grantAuditablePermission(User $user): void
{
    $permission = Permission::firstOrCreate(
        ['name' => PermissionsEnum::Auditable, 'guard_name' => 'web'],
        ['created_at' => now(), 'updated_at' => now()]
    );
    $user->givePermissionTo($permission);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $user->refresh();
}

test('get quote audits is forbidden for user quote type when auditable_id is another user', function () {
    $viewer = User::factory()->create(['last_login' => now()]);
    $otherUser = User::factory()->create(['last_login' => now()]);
    grantAuditablePermission($viewer);

    $this->actingAs($viewer);

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->post('/audits/get-quote-audits', [
        'quote_type' => 'User',
        'auditable_id' => $otherUser->id,
        'jsonData' => true,
    ]);

    $response->assertForbidden()
        ->assertJson([
            'success' => false,
            'message' => 'You do not have permission to view audit logs.',
        ]);
});

test('get quote audits is forbidden when quote type cannot be resolved even with auditable permission', function () {
    $viewer = User::factory()->create(['last_login' => now()]);
    grantAuditablePermission($viewer);

    $this->actingAs($viewer);

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->post('/audits/get-quote-audits', [
        'quote_type' => 'NotARealQuoteModelClassName',
        'auditable_id' => $viewer->id,
        'jsonData' => true,
    ]);

    $response->assertForbidden()
        ->assertJson([
            'success' => false,
            'message' => 'You do not have permission to view audit logs.',
        ]);
});

test('get quote audits allows user to view own user audits with auditable permission', function () {
    $viewer = User::factory()->create(['last_login' => now()]);
    grantAuditablePermission($viewer);

    $this->actingAs($viewer);

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->post('/audits/get-quote-audits', [
        'quote_type' => 'User',
        'auditable_id' => $viewer->id,
        'jsonData' => true,
    ]);

    $response->assertOk();
});
