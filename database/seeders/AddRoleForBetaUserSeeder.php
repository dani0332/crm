<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddRoleForBetaUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $betaUserRole = Role::where('name', 'BETA_USER')->count();
        if ($betaUserRole == 0) {
            Role::create([
                'name' => 'BETA_USER',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $betaUserRoleId = Role::where('name', 'BETA_USER')->pluck('id')[0];
        $paymentListPermission = Permission::where('name', 'payment-list')->where('guard_name', 'web')->get();
        if ($paymentListPermission->isEmpty()) {
            info('Inside If');
            DB::table('permissions')->insert([
                'name' => 'payment-list',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('permissions')->insert([
                'name' => 'payment-edit',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('permissions')->insert([
                'name' => 'payment-create',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $paymentPermissionIds = Permission::whereIn('name', ['payment-edit', 'payment-list', 'payment-create'])->pluck('id');
            info('paymentPermissionIds '.json_encode($paymentPermissionIds));
            $carAdvisorRoleId = Role::where('name', 'CAR_ADVISOR')->pluck('id');
            $carAdvisorPermissionIds = DB::table('role_has_permissions')->where('role_id', $carAdvisorRoleId)->pluck('permission_id');
            foreach ($paymentPermissionIds as $id) {
                DB::table('role_has_permissions')->insert(['role_id' => $betaUserRoleId,
                    'permission_id' => $id,
                ]);
            }
            foreach ($carAdvisorPermissionIds as $id) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $betaUserRoleId,
                    'permission_id' => $id,
                ]);
            }
        }
    }
}
