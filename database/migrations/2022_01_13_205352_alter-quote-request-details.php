<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterQuoteRequestDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('health_quote_request_detail', function (Blueprint $table) {
            $table->bigInteger('lost_reason_id')->nullable();
            $table->foreign('lost_reason_id')->references('id')->on('lost_reasons');

            $table->string('transapp_code', 255)->nullable();
        });
        Schema::table('home_quote_request_detail', function (Blueprint $table) {
            $table->bigInteger('lost_reason_id')->nullable();
            $table->foreign('lost_reason_id')->references('id')->on('lost_reasons');
            $table->string('transapp_code', 255)->nullable();
        });
        Schema::table('life_quote_request_detail', function (Blueprint $table) {
            $table->bigInteger('lost_reason_id')->nullable();
            $table->foreign('lost_reason_id')->references('id')->on('lost_reasons');

            $table->string('transapp_code', 255)->nullable();
        });
        Schema::table('travel_quote_request_detail', function (Blueprint $table) {
            $table->bigInteger('lost_reason_id')->nullable();
            $table->foreign('lost_reason_id')->references('id')->on('lost_reasons');
            $table->string('transapp_code', 255)->nullable();
        });
        Schema::table('car_quote_request_detail', function (Blueprint $table) {
            $table->bigInteger('lost_reason_id')->nullable();
            $table->foreign('lost_reason_id')->references('id')->on('lost_reasons');
            $table->string('transapp_code', 255)->nullable();
        });
        Schema::table('pet_quote_request_detail', function (Blueprint $table) {
            $table->bigInteger('lost_reason_id')->nullable();
            $table->foreign('lost_reason_id')->references('id')->on('lost_reasons');
            $table->string('transapp_code', 255)->nullable();
        });
        Schema::table('business_quote_request_detail', function (Blueprint $table) {
            $table->bigInteger('lost_reason_id')->nullable();
            $table->foreign('lost_reason_id')->references('id')->on('lost_reasons');
            $table->string('transapp_code', 255)->nullable();
        });
        Schema::table('bike_quote_request_detail', function (Blueprint $table) {
            $table->bigInteger('lost_reason_id')->nullable();
            $table->foreign('lost_reason_id')->references('id')->on('lost_reasons');
            $table->string('transapp_code', 255)->nullable();
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
