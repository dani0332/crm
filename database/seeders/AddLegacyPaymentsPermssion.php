<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddLegacyPaymentsPermssion extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permission = Permission::findOrCreate(PermissionsEnum::LEGACY_INSTALLMENTS, 'web');
        $role = Role::where('name', RolesEnum::Accounts)->first();
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $role->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ]
            );
        }
        $role = Role::where('name', RolesEnum::Invoicing)->first();
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $role->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ]
            );
        }
        ////////
        $permission = Permission::findOrCreate(PermissionsEnum::LEGACY_INVOICES, 'web');
        $role = Role::where('name', RolesEnum::Accounts)->first();
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $role->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ]
            );
        }
        $role = Role::where('name', RolesEnum::Invoicing)->first();
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $role->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ]
            );
        }
        ////////
        $permission = Permission::findOrCreate(PermissionsEnum::LEGACY_PAYMENTS, 'web');
        $role = Role::where('name', RolesEnum::Accounts)->first();
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $role->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ]
            );
        }
        $role = Role::where('name', RolesEnum::Invoicing)->first();
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $role->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ]
            );
        }
        ///////
        $permission = Permission::findOrCreate(PermissionsEnum::LEGACY_OTHER_DETAILS, 'web');
        $role = Role::where('name', RolesEnum::Accounts)->first();
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $role->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ]
            );
        }
        $role = Role::where('name', RolesEnum::Invoicing)->first();
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $role->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ]
            );
        }
    }
}
