<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class addRenewalAppSettings extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $carLeadAllocationSwitch = ApplicationStorage::where('key_name', 'CAR_LEAD_ALLOCATION_JOB_SWITCH')->first();
        if ($carLeadAllocationSwitch == null) {
            ApplicationStorage::insert([
                [
                    'key_name' => 'CAR_LEAD_ALLOCATION_JOB_SWITCH',
                    'value' => 0,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
        $carLeadAllocationSwitch = ApplicationStorage::where('key_name', 'CAR_RENEWAL_LEAD_ALLOCATION')->first();
        if ($carLeadAllocationSwitch == null) {
            ApplicationStorage::insert([
                [
                    'key_name' => 'CAR_RENEWAL_LEAD_ALLOCATION',
                    'value' => 1,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
