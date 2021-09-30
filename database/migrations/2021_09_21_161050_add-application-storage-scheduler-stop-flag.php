<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApplicationStorageSchedulerStopFlag extends Migration
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
                'key_name' => 'INSLY_MIGRATION_SCHEDULER_SWITCH',
                'value' => 1,
                'is_active' => 1,
                'created_at' => $datetime,
                'updated_at' => $datetime
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
