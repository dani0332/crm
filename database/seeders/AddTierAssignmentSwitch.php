<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class AddTierAssignmentSwitch extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tierAssignmentSwitch = ApplicationStorage::where('key_name', ApplicationStorageEnums::TIER_ASSIGNMENT_SWITCH)->first();
        if ($tierAssignmentSwitch == null) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::TIER_ASSIGNMENT_SWITCH,
                'value' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
