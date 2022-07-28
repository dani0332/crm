<?php

use App\Models\BaseMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VehicleDetailForCarQuote extends BaseMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('car_quote_vehicle_detail')) {
            Schema::create('car_quote_vehicle_detail', function (Blueprint $table) {
                $table->id()->autoIncrement();

                $table->bigInteger('car_quote_id');
                $table->foreign('car_quote_id')->references('id')->on('car_quote_request');

                $table->string('engine_capacity', '50')->nullable();
                $table->string('cylinder', '10')->nullable();
                $table->string('chassis_number', '100')->nullable();
                $table->string('engine_number', '200')->nullable();
                $table->string('vehicle_color', '15')->nullable();
                $table->string('seating_capacity', '255')->nullable();
                $table->string('vehicle_modified', '10')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vehicle_detail_for_car_quote');
    }
}
