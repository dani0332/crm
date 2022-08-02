<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user_1 = \DB::table('users')->insertGetId([
            'name' => 'Hussain',
            'email' => 'hussain.fakhruddin@afia.ae',
            'password' => Hash::make('1234'),
        ]);
        $user_2 = \DB::table('users')->insertGetId([
            'name' => 'Adeel',
            'email' => 'adeel.rehman@afia.ae',
            'password' => Hash::make('1234'),
        ]);
    }
}
