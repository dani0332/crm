<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTravelFormOptimizationColumnsToTravelQouteRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('travel_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('travel_quote_request', 'direction_code')) {
                $table->string('direction_code', 20)->nullable();
            }
            if (! Schema::hasColumn('travel_quote_request', 'coverage_code')) {
                $table->string('coverage_code', 20)->nullable();
            }
            if (! Schema::hasColumn('travel_quote_request', 'start_date')) {
                $table->date('start_date')->nullable();
            }
            if (! Schema::hasColumn('travel_quote_request', 'end_date')) {
                $table->date('end_date')->nullable();
            }
            if (! Schema::hasColumn('travel_quote_request', 'has_arrived_uae')) {
                $table->boolean('has_arrived_uae')->nullable();
            }
            if (! Schema::hasColumn('travel_quote_request', 'has_arrived_destination')) {
                $table->boolean('has_arrived_destination')->nullable();
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
        //
    }
}
