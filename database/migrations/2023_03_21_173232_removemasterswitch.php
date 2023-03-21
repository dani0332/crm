<?php

use App\Models\ApplicationStorage;
use Illuminate\Database\Migrations\Migration;

class Removemasterswitch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        ApplicationStorage::where('key_name', 'CAR_LEAD_ALLOCATION_MASTER_SWITCH')->delete();
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
