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
        $sibDripListId = ApplicationStorage::where('key_name', 'SIB_CAR_DRIP_LIST_ID')->count();
        if ($sibDripListId == 0) {
            $sibDripListId = ApplicationStorage::create([
                'key_name' => 'SIB_CAR_DRIP_LIST_ID',
                'value' => '50',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $sibDripListId = ApplicationStorage::where('key_name', 'SIB_HEALTH_EBP_LIST_ID')->count();
        if ($sibDripListId == 0) {
            $sibDripListId = ApplicationStorage::create([
                'key_name' => 'SIB_HEALTH_EBP_LIST_ID',
                'value' => '128',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
