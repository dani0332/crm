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
            if (!Schema::hasColumn('travel_quote_request', 'travelling_where')) {
                $table->string('travelling_where', 20)->nullable();
                $table->foreign('travelling_where')->references('code')->on('lookups');
            }
            if (!Schema::hasColumn('travel_quote_request', 'travel_coverage')) {
                $table->string('travel_coverage', 20)->nullable();
                $table->foreign('travel_coverage')->references('code')->on('lookups');
            }
            if (!Schema::hasColumn('travel_quote_request', 'travel_start_date')) {
                $table->date('travel_start_date')->nullable(); 
            }
            if (!Schema::hasColumn('travel_quote_request', 'travel_end_date')) {
                $table->date('travel_end_date')->nullable(); 
            }
            if (!Schema::hasColumn('travel_quote_request', 'has_arrived_uae')) {
                $table->boolean('has_arrived_uae')->nullable();
            }
            if (!Schema::hasColumn('travel_quote_request', 'has_arrived_destination')) {
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
