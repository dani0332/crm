<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApplicationStorage;
use App\Enums\ApplicationStorageEnums;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AddSICHealthWorkFlowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sicHealthWorkFlow = ApplicationStorage::where('key_name', ApplicationStorageEnums::SIC_HEALTH_WORKFLOW_NAME)->first();
        if (empty($sicHealthWorkFlow)) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::SIC_HEALTH_WORKFLOW_NAME,
                'value' => '0',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
    }
}
