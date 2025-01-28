<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class addCarLeadAllocationFetchStartDate extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // LEAD_ALLOCATION_START_DATE_FOR_LEADS
        $allocationStartDateForCar = ApplicationStorage::where('key_name', 'CAR_LEAD_ALLOCATION_START_DATE_FOR_LEADS')->get();
        if (count($allocationStartDateForCar) == 0) {
            DB::table('application_storage')->insert([[
                'key_name' => 'CAR_LEAD_ALLOCATION_START_DATE_FOR_LEADS',
                'value' => '2022-01-28 23:59:59',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
        }
    }
}
