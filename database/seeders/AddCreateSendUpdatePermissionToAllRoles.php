<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddCreateSendUpdatePermissionToAllRoles extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerShowPermission = Permission::where('name', PermissionsEnum::SEND_UPDATE_CREATE)->first();
        if ($customerShowPermission == null) {
            DB::table('permissions')->insert([
                'name' => PermissionsEnum::SEND_UPDATE_CREATE,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $customerShowPermissionId = Permission::where('name', PermissionsEnum::SEND_UPDATE_CREATE)->first()->id;

            if ($customerShowPermissionId) {
                $roles = Role::all();
                foreach ($roles as $role) {
                    DB::table('role_has_permissions')->insert(
                        [
                            'role_id' => $role->id,
                            'permission_id' => $customerShowPermissionId,
                        ]
                    );
                }
            }
        }
    }
}
