<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AiAdvisorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'instant@alfred.insurancemarket.ae'],
            [
                'name' => 'Sarah',
                'is_ai_user' => true,
                'password' => Hash::make(Str::random(30)),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'landline_no' => '048185799',
                'is_active' => 1,
            ],
        );

    }
}
