<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PetInsuranceAddEditPermissions extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('permissions')->insert([
            'name' => 'pet-quotes-edit',
            'guard_name' => 'web',
        ]);
    }
}
