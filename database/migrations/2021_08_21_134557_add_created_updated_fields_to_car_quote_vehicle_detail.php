<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedUpdatedFieldsToCarQuoteVehicleDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_vehicle_detail', function (Blueprint $table) {
            if (! Schema::hasColumn('car_quote_vehicle_detail', 'created_by')) {
                $table->string('created_by')->nullable();
            }
            if (! Schema::hasColumn('car_quote_vehicle_detail', 'updated_by')) {
                $table->string('updated_by')->nullable();
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
