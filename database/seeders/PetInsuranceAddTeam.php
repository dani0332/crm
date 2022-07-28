<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PetInsuranceAddTeam extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('teams')->insert([
            'name' => 'Pet',
        ]);
    }
}
