<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AutomationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_AUTOMATION_WORKFLOW_URL],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/1670bb39-565e-4825-96c1-cf9e516dec5f/invoke-sync',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
