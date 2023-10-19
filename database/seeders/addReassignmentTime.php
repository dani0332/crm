<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class addReassignmentTime extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $startTime = ApplicationStorage::where('key_name', ApplicationStorageEnums::REASSIGNMENT_START_TIME)->first();
        if ($startTime == null) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::REASSIGNMENT_START_TIME,
                'value' => '10:00',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }

        $endTime = ApplicationStorage::where('key_name', ApplicationStorageEnums::REASSIGNMENT_END_TIME)->first();
        if ($endTime == null) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::REASSIGNMENT_END_TIME,
                'value' => '18:30',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }

        $inactive = ApplicationStorage::where('key_name', ApplicationStorageEnums::USER_INACTIVE_THRESHOLD)->first();
        if ($inactive == null) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::USER_INACTIVE_THRESHOLD,
                'value' => '300',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
    }
}
