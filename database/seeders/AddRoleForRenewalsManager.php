<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddRoleForRenewalsManager extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $renewalsManagerRole = Role::where('name', 'RENEWALS_MANAGER')->count();
        if ($renewalsManagerRole == 0) {
            $renewalsManager = Role::create([
                'name' => 'RENEWALS_MANAGER',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $renewalsRoleId = Role::where('name', 'RENEWALS_MANAGEMENT')->pluck('id');
            $renewalsPermissionIds = DB::table('role_has_permissions')->where('role_id', $renewalsRoleId)->pluck('permission_id');

            foreach ($renewalsPermissionIds as $id) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $renewalsManager->id,
                    'permission_id' => $id,
                ]);
            }
        }
    }
}
