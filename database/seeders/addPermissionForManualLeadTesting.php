<?php

namespace Database\Seeders;

use App\Models\Permission;
use DB;
use Illuminate\Database\Seeder;

class addPermissionForManualLeadTesting extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permission = Permission::where('name', 'manual-lead-assignment-QA')->first();
        if ($permission == null) {
            DB::table('permissions')->insert([
                'name' => 'manual-lead-assignment-QA',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
