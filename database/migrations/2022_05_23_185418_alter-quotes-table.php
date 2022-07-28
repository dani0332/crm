<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('car_quote_request', 'renewal_batch')) {
                $table->string('renewal_batch', 50)->nullable()->default(null)->change();
            }
        });

        Schema::table('health_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('health_quote_request', 'renewal_batch')) {
                $table->string('renewal_batch', 50)->nullable()->default(null)->change();
            }
        });

        Schema::table('travel_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('travel_quote_request', 'renewal_batch')) {
                $table->string('renewal_batch', 50)->nullable()->default(null)->change();
            }
        });

        Schema::table('life_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('life_quote_request', 'renewal_batch')) {
                $table->string('renewal_batch', 50)->nullable()->default(null)->change();
            }
        });

        Schema::table('home_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('home_quote_request', 'renewal_batch')) {
                $table->string('renewal_batch', 50)->nullable()->default(null)->change();
            }
        });

        Schema::table('yacht_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('yacht_quote_request', 'renewal_batch')) {
                $table->string('renewal_batch', 50)->nullable()->default(null)->change();
            }
        });

        Schema::table('bike_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('bike_quote_request', 'renewal_batch')) {
                $table->string('renewal_batch', 50)->nullable()->default(null)->change();
            }
        });

        Schema::table('business_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('business_quote_request', 'renewal_batch')) {
                $table->string('renewal_batch', 50)->nullable()->default(null)->change();
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
