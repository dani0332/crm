<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPreviousAdvisorIdToQuotesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('health_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('health_quote_request', 'previous_advisor_id')) {
                $table->unsignedBigInteger('previous_advisor_id')->nullable();
                $table->foreign('previous_advisor_id')->references('id')->on('users')->onDelete('no action');
            }
        });

        Schema::table('travel_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('travel_quote_request', 'previous_advisor_id')) {
                $table->unsignedBigInteger('previous_advisor_id')->nullable();
                $table->foreign('previous_advisor_id')->references('id')->on('users')->onDelete('no action');
            }
        });

        Schema::table('life_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('life_quote_request', 'previous_advisor_id')) {
                $table->unsignedBigInteger('previous_advisor_id')->nullable();
                $table->foreign('previous_advisor_id')->references('id')->on('users')->onDelete('no action');
            }
        });

        Schema::table('home_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('home_quote_request', 'previous_advisor_id')) {
                $table->unsignedBigInteger('previous_advisor_id')->nullable();
                $table->foreign('previous_advisor_id')->references('id')->on('users')->onDelete('no action');
            }
        });

        Schema::table('yacht_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('yacht_quote_request', 'previous_advisor_id')) {
                $table->unsignedBigInteger('previous_advisor_id')->nullable();
                $table->foreign('previous_advisor_id')->references('id')->on('users')->onDelete('no action');
            }
        });

        Schema::table('bike_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('bike_quote_request', 'previous_advisor_id')) {
                $table->unsignedBigInteger('previous_advisor_id')->nullable();
                $table->foreign('previous_advisor_id')->references('id')->on('users')->onDelete('no action');
            }
        });

        Schema::table('business_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('business_quote_request', 'previous_advisor_id')) {
                $table->unsignedBigInteger('previous_advisor_id')->nullable();
                $table->foreign('previous_advisor_id')->references('id')->on('users')->onDelete('no action');
            }
        });

        Schema::table('car_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('car_quote_request', 'previous_advisor_id')) {
                $table->unsignedBigInteger('previous_advisor_id')->nullable();
                $table->foreign('previous_advisor_id')->references('id')->on('users')->onDelete('no action');
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
