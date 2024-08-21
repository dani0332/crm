<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTravelRegionIdColumnToNationalityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('nationality', function (Blueprint $table) {
            if (! Schema::hasColumn('nationality', 'travel_region_id')) {
                $table->integer('travel_region_id')->nullable();
                $table->foreign('travel_region_id')->references('id')->on('region');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
}
