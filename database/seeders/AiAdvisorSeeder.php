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
            ['email' => 'ai@insurancemarket.ae'],
            [
                'name' => 'AI Advisor',
                'is_ai_user' => true,
                'password' => Hash::make(Str::random(30)),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }
}
