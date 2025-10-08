<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
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
                'value' => 'https://api.bird.com/workspaces/7e7ef00d-88c7-446a-81bf-c3b6cd522318/flows/86c66fc8-6892-4545-8cb9-e5c8aa1627a3/invoke-sync',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
