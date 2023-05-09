<?php

namespace Database\Seeders;

use App\Models\Permission;
use DB;
use Illuminate\Database\Seeder;

class addPermissionForCustomer extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $customerShowrPermission = Permission::where('name', 'customers-show')->first();
        if ($customerShowrPermission == null) {
            DB::table('permissions')->insert([
                'name' => 'customers-show',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
