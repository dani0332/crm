<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class addAdvisorConvertionReportBatchStartDate extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // CONVERSATION_REPORT_BATCH_START_DATE
        $allocationStartDateForCar = ApplicationStorage::where('key_name', 'CONVERSATION_REPORT_BATCH_START_DATE')->get();
        if (count($allocationStartDateForCar) == 0) {
            DB::table('application_storage')->insert([[
                'key_name' => 'CONVERSATION_REPORT_BATCH_START_DATE',
                'value' => '2022-01-28 23:59:59',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
        }
    }
}
