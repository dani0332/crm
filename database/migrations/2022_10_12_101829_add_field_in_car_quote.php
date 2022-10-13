<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldInCarQuote extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('car_quote_request')) {
            Schema::table('car_quote_request', function (Blueprint $table) {
                if (! Schema::hasColumn('car_quote_request', 'tier_id')) {
                    $table->unsignedBigInteger('tier_id')->nullable();
                    $table->foreign('tier_id')->references('id')->on('tiers')->onDelete('no action');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('car_quote', function (Blueprint $table) {
            //
        });
    }
}
