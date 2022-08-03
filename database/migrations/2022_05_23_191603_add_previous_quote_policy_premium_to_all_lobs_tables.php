<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPreviousQuotePolicyPremiumToAllLobsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('car_quote_request', 'previous_quote_policy_premium')) {
                $table->decimal('previous_quote_policy_premium')->nullable();
            }
        });

        Schema::table('health_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('health_quote_request', 'previous_quote_policy_premium')) {
                $table->decimal('previous_quote_policy_premium')->nullable();
            }
        });

        Schema::table('travel_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('travel_quote_request', 'previous_quote_policy_premium')) {
                $table->decimal('previous_quote_policy_premium')->nullable();
            }
        });

        Schema::table('life_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('life_quote_request', 'previous_quote_policy_premium')) {
                $table->decimal('previous_quote_policy_premium')->nullable();
            }
        });

        Schema::table('home_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('home_quote_request', 'previous_quote_policy_premium')) {
                $table->decimal('previous_quote_policy_premium')->nullable();
            }
        });

        Schema::table('yacht_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('yacht_quote_request', 'previous_quote_policy_premium')) {
                $table->decimal('previous_quote_policy_premium')->nullable();
            }
        });

        Schema::table('bike_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('bike_quote_request', 'previous_quote_policy_premium')) {
                $table->decimal('previous_quote_policy_premium')->nullable();
            }
        });

        Schema::table('business_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('business_quote_request', 'previous_quote_policy_premium')) {
                $table->decimal('previous_quote_policy_premium')->nullable();
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
        Schema::table('all_lobs_tables', function (Blueprint $table) {
            //
        });
    }
}
