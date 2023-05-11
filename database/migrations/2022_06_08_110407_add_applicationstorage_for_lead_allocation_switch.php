<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

class AddApplicationstorageForLeadAllocationSwitch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $datetime = date('Y-m-d H:i:s');
        $dateTimeFormat = config('constants.DB_DATE_FORMAT_MATCH');

        DB::table('application_storage')->insert(
            [
                'key_name' => 'LEAD_ALLOCATION_JOB_SWITCH',
                'value' => 1,
                'is_active' => 1,
                'created_at' => $datetime,
                'updated_at' => $datetime,
            ]
        );

        DB::table('application_storage')->insert(
            [
                'key_name' => 'LEAD_ALLOCATION_START_DATE_FOR_LEADS',
                'value' => Carbon::now()->endOfDay()->format($dateTimeFormat),
                'is_active' => 1,
                'created_at' => Carbon::now()->format($dateTimeFormat),
                'updated_at' => Carbon::now()->format($dateTimeFormat),
            ]
        );

        DB::table('application_storage')->insert(
            [
                'key_name' => 'LEAD_ALLOCATION_UNAVAILABILITY_TIME',
                'value' => Carbon::now()->endOfDay()->format($dateTimeFormat),
                'is_active' => 1,
                'created_at' => Carbon::now()->format($dateTimeFormat),
                'updated_at' => Carbon::now()->format($dateTimeFormat),
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
