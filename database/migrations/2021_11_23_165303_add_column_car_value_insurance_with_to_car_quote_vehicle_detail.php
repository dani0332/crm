<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnCarValueInsuranceWithToCarQuoteVehicleDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_vehicle_detail', function (Blueprint $table) {
            if (! Schema::hasColumn('car_quote_vehicle_detail', 'car_value')) {
                $table->string('car_value', 100)->nullable();
            }
            if (! Schema::hasColumn('car_quote_vehicle_detail', 'currently_insured_with')) {
                $table->string('currently_insured_with', 200)->nullable();
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
