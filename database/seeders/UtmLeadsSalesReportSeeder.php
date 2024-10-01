<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UtmLeadsSalesReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $role = Role::firstOrCreate([
            'name' => 'SENIOR_MANAGEMENT',
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $permission = Permission::firstOrCreate([
            'name' => 'utm-leads-sales-report',
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! empty($role) && ! empty($permission)) {
            $record = DB::table('role_has_permissions')->where('role_id', $role->id)->where('permission_id', $permission->id)->first();
            if (empty($record)) {
                DB::table('role_has_permissions')->insert(
                    [
                        'role_id' => $role->id,
                        'permission_id' => $permission->id,
                    ]
                );
            }

        }
    }
}
