<?php

use App\Models\ApplicationStorage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class Removemasterswitch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("delete from application_storage where key_name = 'CAR_LEAD_ALLOCATION_MASTER_SWITCH'");
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
