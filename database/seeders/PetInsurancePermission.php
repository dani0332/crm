<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PetInsurancePermission extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('permissions')->insert([
            'name' => 'pet-quotes-list',
            'guard_name' => 'web',
        ]);
        \DB::table('permissions')->insert([
            'name' => 'pet-quotes-create',
            'guard_name' => 'web',
        ]);
        \DB::table('permissions')->insert([
            'name' => 'pet-quotes-update',
            'guard_name' => 'web',
        ]);
        \DB::table('permissions')->insert([
            'name' => 'pet-quotes-delete',
            'guard_name' => 'web',
        ]);
        \DB::table('permissions')->insert([
            'name' => 'pet-quotes-view',
            'guard_name' => 'web',
        ]);
    }
}
