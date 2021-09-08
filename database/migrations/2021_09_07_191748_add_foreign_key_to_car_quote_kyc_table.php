<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeyToCarQuoteKycTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_kyc', function (Blueprint $table) {
            $table->foreign('car_quote_id')->references('id')->on('car_quote_request');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('car_quote_kyc', function (Blueprint $table) {
            //
        });
    }
}
