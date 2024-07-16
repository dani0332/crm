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
        $permission = Permission::where('name', PermissionsEnum::DepartmentCreate ?? 'department-create')->first();
        if ($permission == null) {
            DB::table('permissions')->insert([
                'name' => PermissionsEnum::DepartmentCreate ?? 'department-create',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('permissions')->insert([
                'name' => PermissionsEnum::DepartmentUpdate ?? 'department-update',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $adminRoleId = Role::where('name', RolesEnum::Admin)->first()->id;
            $departmentCreatePermissionViewId = Permission::where('name', PermissionsEnum::DepartmentCreate ?? 'department-create')->first()->id;
            $departmentUpdatePermissionViewId = Permission::where('name', PermissionsEnum::DepartmentUpdate ?? 'department-update')->first()->id;
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
