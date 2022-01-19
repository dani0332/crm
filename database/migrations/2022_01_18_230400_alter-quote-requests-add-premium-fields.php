<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterQuoteRequestsAddPremiumFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('health_quote_request', function (Blueprint $table) {
            $table->decimal('premium')->nullable();
        });
        Schema::table('home_quote_request', function (Blueprint $table) {
            $table->decimal('premium')->nullable();
        });
        Schema::table('life_quote_request', function (Blueprint $table) {
            $table->decimal('premium')->nullable();
        });
        Schema::table('travel_quote_request', function (Blueprint $table) {
            $table->decimal('premium')->nullable();
        });
        Schema::table('pet_quote_request', function (Blueprint $table) {
            $table->decimal('premium')->nullable();
        });
        Schema::table('bike_quote_request', function (Blueprint $table) {
            $table->decimal('premium')->nullable();
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
