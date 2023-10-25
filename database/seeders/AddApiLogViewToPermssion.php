<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddApiLogViewToPermssion extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permission = Permission::findOrCreate(PermissionsEnum::API_LOG_VIEW, 'web');
        $carManagerRole = Role::where('name', RolesEnum::CarManager)->first();
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $carManagerRole->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $carManagerRole->id,
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

        $carAdvisorRole = Role::where('name', RolesEnum::CarAdvisor)->first();
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $carAdvisorRole->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $carAdvisorRole->id,
                    'permission_id' => $permission->id,
                ]
            );
        }
    }
}
