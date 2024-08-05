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

        $birdOCBEmail = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_OCB_FOLLOWUP_TEMPLATE)->first();
        if (empty($birdOCBEmail)) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::BIRD_SIC_HEALTH_OCB_FOLLOWUP_TEMPLATE,
                'value' => 'https://capture.eu-west-1.nest.messagebird.com/webhooks/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/c95e7c6b-123e-49dd-917f-f617181b61da',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
        $birdOCBNonAdvEmail = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_OCB_NON_ADVISOR_FOLLOWUP_TEMPLATE)->first();
        if (empty($birdOCBNonAdvEmail)) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::BIRD_SIC_HEALTH_OCB_NON_ADVISOR_FOLLOWUP_TEMPLATE,
                'value' => 'https://capture.eu-west-1.nest.messagebird.com/webhooks/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/97be42ac-0660-40ed-aa6c-2a070f01ffc0',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }

        $birdSICHealthWorkFlow = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW)->first();
        if (empty($birdSICHealthWorkFlow)) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW,
                'value' => 'https://capture.eu-west-1.nest.messagebird.com/webhooks/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/79b9011e-ff3b-4a5c-a63f-fed3d0743a7f',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }


    }
}
