<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddRoleForMarketingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $marketingOperationsRole = Role::where('name', 'MARKETING_OPERATIONS')->count();
        if ($marketingOperationsRole == 0) {
            $marketingOperations = Role::create([
                'name' => 'MARKETING_OPERATIONS',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $qaRoleId = Role::where('name', 'QA')->pluck('id');
            $qaPermissionIds = DB::table('role_has_permissions')->where('role_id', $qaRoleId)->pluck('permission_id');

            foreach ($qaPermissionIds as $id) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $marketingOperations->id,
                    'permission_id' => $id,
                ]);
            }
        }
    }
}
