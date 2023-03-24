<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class addCarDripListKey extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sibCarDripListId = ApplicationStorage::where('key_name', 'SIB_CAR_DRIP_LIST_ID')->count();
        if ($sibCarDripListId == 0) {
            $sibCarDripListId = ApplicationStorage::create([
                'key_name' => 'SIB_CAR_DRIP_LIST_ID',
                'value' => '50',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $sibHealthListId = ApplicationStorage::where('key_name', 'SIB_HEALTH_EBP_LIST_ID')->count();
        if ($sibHealthListId == 0) {
            $sibHealthListId = ApplicationStorage::create([
                'key_name' => 'SIB_HEALTH_EBP_LIST_ID',
                'value' => '128',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
