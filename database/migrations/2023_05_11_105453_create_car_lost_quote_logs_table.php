<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarLostQuoteLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('car_lost_quote_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('car_quote_request_id');
            $table->foreign('car_quote_request_id')->references('id')->on('car_quote_request');

            $table->unsignedBigInteger('advisor_id');
            $table->foreign('advisor_id')->references('id')->on('users');

            $table->integer('quote_status_id');
            $table->foreign('quote_status_id')->references('id')->on('quote_status');

            $table->string('status', 30);
            $table->unsignedBigInteger('action_by_id')->nullable();
            $table->foreign('action_by_id')->references('id')->on('users');

            $table->unsignedBigInteger('reason_id')->nullable();
            $table->foreign('reason_id')->references('id')->on('lookups');

            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('car_lost_quote_logs');
    }
}
