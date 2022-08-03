<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAllQuoteTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('car_quote_request', 'policy_start_date')) {
                $table->dateTime('policy_start_date')->nullable();
            }
            if (! Schema::hasColumn('car_quote_request', 'policy_issuance_date')) {
                $table->dateTime('policy_issuance_date')->nullable();
            }
        });

        Schema::table('health_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('health_quote_request', 'policy_start_date')) {
                $table->dateTime('policy_start_date')->nullable();
            }
            if (! Schema::hasColumn('health_quote_request', 'policy_issuance_date')) {
                $table->dateTime('policy_issuance_date')->nullable();
            }
        });

        Schema::table('travel_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('travel_quote_request', 'policy_start_date')) {
                $table->dateTime('policy_start_date')->nullable();
            }
            if (! Schema::hasColumn('travel_quote_request', 'policy_issuance_date')) {
                $table->dateTime('policy_issuance_date')->nullable();
            }
        });

        Schema::table('life_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('life_quote_request', 'policy_start_date')) {
                $table->dateTime('policy_start_date')->nullable();
            }
            if (! Schema::hasColumn('life_quote_request', 'policy_issuance_date')) {
                $table->dateTime('policy_issuance_date')->nullable();
            }
        });

        Schema::table('home_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('home_quote_request', 'policy_start_date')) {
                $table->dateTime('policy_start_date')->nullable();
            }
            if (! Schema::hasColumn('home_quote_request', 'policy_issuance_date')) {
                $table->dateTime('policy_issuance_date')->nullable();
            }
        });

        Schema::table('yacht_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('yacht_quote_request', 'policy_start_date')) {
                $table->dateTime('policy_start_date')->nullable();
            }
            if (! Schema::hasColumn('yacht_quote_request', 'policy_issuance_date')) {
                $table->dateTime('policy_issuance_date')->nullable();
            }
        });

        Schema::table('bike_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('bike_quote_request', 'policy_start_date')) {
                $table->dateTime('policy_start_date')->nullable();
            }
            if (! Schema::hasColumn('bike_quote_request', 'policy_issuance_date')) {
                $table->dateTime('policy_issuance_date')->nullable();
            }
        });

        Schema::table('business_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('business_quote_request', 'policy_start_date')) {
                $table->dateTime('policy_start_date')->nullable();
            }
            if (! Schema::hasColumn('business_quote_request', 'policy_issuance_date')) {
                $table->dateTime('policy_issuance_date')->nullable();
            }
        });

        Schema::table('pet_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('pet_quote_request', 'policy_start_date')) {
                $table->dateTime('policy_start_date')->nullable();
            }
            if (! Schema::hasColumn('pet_quote_request', 'policy_issuance_date')) {
                $table->dateTime('policy_issuance_date')->nullable();
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
