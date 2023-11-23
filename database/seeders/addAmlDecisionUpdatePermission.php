<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class addAmlDecisionUpdatePermission extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Need to refactor this code.
        $permission = Permission::findOrCreate(PermissionsEnum::AMLDecisionUpdate, 'web');
        $adminRole = Role::where('name', RolesEnum::Admin)->first();
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                ]
            );
        }

        $complianceRole = Role::where('name', RolesEnum::COMPLIANCE)->first();
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $complianceRole->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $complianceRole->id,
                    'permission_id' => $permission->id,
                ]
            );
        }

        $permission = Permission::findOrCreate(PermissionsEnum::AMLDecisionUpdateTrueMatch, 'web');
        $complianceSuperRole = Role::where('name', RolesEnum::ComplianceSuperUser)->first();
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $complianceSuperRole->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $complianceSuperRole->id,
                    'permission_id' => $permission->id,
                ]
            );
        }

        $adminRole = Role::where('name', RolesEnum::Admin)->first();
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                ]
            );
        }
    }
}
