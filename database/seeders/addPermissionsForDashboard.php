<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use DB;
use Illuminate\Database\Seeder;

class addPermissionsForDashboard extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permission = Permission::where('name', 'tpl-dashboard-view')->first();
        if ($permission == null) {
            DB::table('permissions')->insert([
                'name' => 'tpl-dashboard-view',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('permissions')->insert([
                'name' => 'comprehensive-dashboard-view',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('permissions')->insert([
                'name' => 'main-dashboard-view',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $adminRoleId = Role::where('name', 'ADMIN')->first()->id;
            $tplPermissionViewId = Permission::where('name', 'tpl-dashboard-view')->first()->id;
            $compPermissionViewId = Permission::where('name', 'comprehensive-dashboard-view')->first()->id;
            $mainPermissionViewId = Permission::where('name', 'main-dashboard-view')->first()->id;
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRoleId,
                    'permission_id' => $tplPermissionViewId,
                ]);
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRoleId,
                    'permission_id' => $compPermissionViewId,
                ]);
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRoleId,
                    'permission_id' => $mainPermissionViewId,
                ]);
        }
    }
}
