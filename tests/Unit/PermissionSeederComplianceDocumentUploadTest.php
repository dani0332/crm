<?php

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    $db = DB::connection('sqlite');
    $db->table('role_has_permissions')->delete();
    $db->table('permissions')->delete();
    $db->table('roles')->delete();
});

it('creates compliance-document-upload permission and assigns it to COMPLIANCE_SUPER_USER role', function () {
    DB::connection('sqlite')->table('roles')->insert([
        'name' => RolesEnum::ComplianceSuperUser,
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    (new PermissionSeeder)->run();

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $permission = Permission::query()
        ->where('name', PermissionsEnum::COMPLIANCE_DOCUMENT_UPLOAD)
        ->where('guard_name', 'web')
        ->first();

    expect($permission)->not->toBeNull();

    $role = Role::query()->where('name', RolesEnum::ComplianceSuperUser)->where('guard_name', 'web')->first();
    expect($role)->not->toBeNull();
    expect($role->hasPermissionTo(PermissionsEnum::COMPLIANCE_DOCUMENT_UPLOAD))->toBeTrue();
});

it('creates permission when COMPLIANCE_SUPER_USER role is missing without throwing', function () {
    expect(fn () => (new PermissionSeeder)->run())->not->toThrow(Throwable::class);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect(Permission::query()->where('name', PermissionsEnum::COMPLIANCE_DOCUMENT_UPLOAD)->exists())->toBeTrue();
});
