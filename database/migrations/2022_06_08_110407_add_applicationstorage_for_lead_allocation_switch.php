<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        DB::table('application_storage')->insert(
            array(
                'key_name' => 'LEAD_ALLOCATION_JOB_SWITCH',
                'value' => 1,
                'is_active' => 1,
                'created_at' => $datetime,
                'updated_at' => $datetime
            )
        );

        DB::table('application_storage')->insert(
            array(
                'key_name' => 'LEAD_ALLOCATION_START_DATE_FOR_LEADS',
                'value' => Carbon::now()->endOfDay()->format('Y-m-d H:i:s'),
                'is_active' => 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            )
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
