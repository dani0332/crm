<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterDatatypeAdvisorAssignedDateAllLobsDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_request_detail', function (Blueprint $table) {
            $table->dateTime('advisor_assigned_date')->nullable()->default(null)->change();
        });
        Schema::table('health_quote_request_detail', function (Blueprint $table) {
            $table->dateTime('advisor_assigned_date')->nullable()->default(null)->change();
        });
        Schema::table('travel_quote_request_detail', function (Blueprint $table) {
            $table->dateTime('advisor_assigned_date')->nullable()->default(null)->change();
        });
        Schema::table('life_quote_request_detail', function (Blueprint $table) {
            $table->dateTime('advisor_assigned_date')->nullable()->default(null)->change();
        });
        Schema::table('home_quote_request_detail', function (Blueprint $table) {
            $table->dateTime('advisor_assigned_date')->nullable()->default(null)->change();
        });
        Schema::table('yacht_quote_request_detail', function (Blueprint $table) {
            $table->dateTime('advisor_assigned_date')->nullable()->default(null)->change();
        });
        Schema::table('bike_quote_request_detail', function (Blueprint $table) {
            $table->dateTime('advisor_assigned_date')->nullable()->default(null)->change();
        });
        Schema::table('business_quote_request_detail', function (Blueprint $table) {
            $table->dateTime('advisor_assigned_date')->nullable()->default(null)->change();
        });
        Schema::table('pet_quote_request_detail', function (Blueprint $table) {
            $table->dateTime('advisor_assigned_date')->nullable()->default(null)->change();
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
