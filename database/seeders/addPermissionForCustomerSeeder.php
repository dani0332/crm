<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class addPermissionForCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $customerShowPermission = Permission::where('name', 'customers-show')->first();
        if ($customerShowPermission == null) {
            DB::table('permissions')->insert([
                'name' => 'customers-show',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $customerShowPermissionId = Permission::where('name', 'customers-show')->first()->id;

            if ($customerShowPermissionId) {
                $adminRoleId = Role::where('name', 'ADMIN')->first()->id;
                DB::table('role_has_permissions')->insert(
                    [
                        'role_id' => $adminRoleId,
                        'permission_id' => $customerShowPermissionId,
                    ]
                );
            }
        }
    }
}
