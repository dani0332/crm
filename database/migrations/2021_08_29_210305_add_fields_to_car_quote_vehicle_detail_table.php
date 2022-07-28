<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToCarQuoteVehicleDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_vehicle_detail', function (Blueprint $table) {
            if (! Schema::hasColumn('car_quote_vehicle_detail', 'specs')) {
                $table->string('specs')->nullable();
            }
            if (! Schema::hasColumn('car_quote_vehicle_detail', 'current_cover')) {
                $table->string('current_cover')->nullable();
            }
            if (! Schema::hasColumn('car_quote_vehicle_detail', 'date_first_registration')) {
                $table->date('date_first_registration')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('car_quote_vehicle_detail', function (Blueprint $table) {
            //
        });
    }
}
