<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permission = Permission::where('name', PermissionsEnum::DEPARTMENT_LIST ?? 'department-list')->first();
        if ($permission == null) {
            DB::table('permissions')->insert([
                'name' => PermissionsEnum::DEPARTMENT_LIST ?? 'department-list',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('permissions')->insert([
                'name' => PermissionsEnum::DEPARTMENT_CREATE ?? 'department-create',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('permissions')->insert([
                'name' => PermissionsEnum::DEPARTMENT_UPDATE ?? 'department-update',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $adminRoleId = Role::where('name', RolesEnum::Admin)->first()->id;
            $departmentListPermissionViewId = Permission::where('name', PermissionsEnum::DEPARTMENT_LIST ?? 'department-list')->first()->id;
            $departmentCreatePermissionViewId = Permission::where('name', PermissionsEnum::DEPARTMENT_CREATE ?? 'department-create')->first()->id;
            $departmentUpdatePermissionViewId = Permission::where('name', PermissionsEnum::DEPARTMENT_UPDATE ?? 'department-update')->first()->id;
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRoleId,
                    'permission_id' => $departmentListPermissionViewId,
                ]);
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRoleId,
                    'permission_id' => $departmentCreatePermissionViewId,
                ]);
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRoleId,
                    'permission_id' => $departmentUpdatePermissionViewId,
                ]);
        }
    }
}
