<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class AddTierAssignmentProcessStartDate extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tierAssignmentSwitch = ApplicationStorage::where('key_name', ApplicationStorageEnums::TIER_ASSIGNMENT_PROCESS_START_DATE)->first();
        if ($tierAssignmentSwitch == null) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::TIER_ASSIGNMENT_PROCESS_START_DATE,
                'value' => '2022-12-01 00:00:01',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
