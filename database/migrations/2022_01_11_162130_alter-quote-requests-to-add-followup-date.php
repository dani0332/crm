<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterQuoteRequestsToAddFollowupDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('health_quote_request_detail', function (Blueprint $table) {
            $table->timestamp('next_followup_date')->nullable();
            $table->text('notes')->nullable();
        });
        Schema::table('home_quote_request_detail', function (Blueprint $table) {
            $table->timestamp('next_followup_date')->nullable();
            $table->text('notes')->nullable();
        });
        Schema::table('life_quote_request_detail', function (Blueprint $table) {
            $table->timestamp('next_followup_date')->nullable();
            $table->text('notes')->nullable();
        });
        Schema::table('travel_quote_request_detail', function (Blueprint $table) {
            $table->timestamp('next_followup_date')->nullable();
            $table->text('notes')->nullable();
        });
        Schema::table('car_quote_request_detail', function (Blueprint $table) {
            $table->timestamp('next_followup_date')->nullable();
            $table->text('notes')->nullable();
        });
        Schema::table('pet_quote_request_detail', function (Blueprint $table) {
            $table->timestamp('next_followup_date')->nullable();
            $table->text('notes')->nullable();
        });
        Schema::table('business_quote_request_detail', function (Blueprint $table) {
            $table->timestamp('next_followup_date')->nullable();
            $table->text('notes')->nullable();
        });
        Schema::table('bike_quote_request_detail', function (Blueprint $table) {
            $table->timestamp('next_followup_date')->nullable();
            $table->text('notes')->nullable();
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
