<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropColumnInsurerQuoteNoFromAllQuotesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('car_quote_request', 'insurer_quote_no')) {
                $table->dropColumn('insurer_quote_no');
            }
        });
        Schema::table('health_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('health_quote_request', 'insurer_quote_no')) {
                $table->dropColumn('insurer_quote_no');
            }
        });
        Schema::table('travel_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('travel_quote_request', 'insurer_quote_no')) {
                $table->dropColumn('insurer_quote_no');
            }
        });
        Schema::table('life_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('life_quote_request', 'insurer_quote_no')) {
                $table->dropColumn('insurer_quote_no');
            }
        });
        Schema::table('home_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('home_quote_request', 'insurer_quote_no')) {
                $table->dropColumn('insurer_quote_no');
            }
        });
        Schema::table('yacht_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('yacht_quote_request', 'insurer_quote_no')) {
                $table->dropColumn('insurer_quote_no');
            }
        });
        Schema::table('bike_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('bike_quote_request', 'insurer_quote_no')) {
                $table->dropColumn('insurer_quote_no');
            }
        });
        Schema::table('business_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('business_quote_request', 'insurer_quote_no')) {
                $table->dropColumn('insurer_quote_no');
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
