<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class addCarAllocationMasterSwitch extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $masterSwitch = ApplicationStorage::where('key_name', 'CAR_LEAD_ALLOCATION_MASTER_SWITCH')->first();
        if ($masterSwitch == null) {
            ApplicationStorage::insert([
                'key_name' => 'CAR_LEAD_ALLOCATION_MASTER_SWITCH',
                'value' => '1',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
    }
}
