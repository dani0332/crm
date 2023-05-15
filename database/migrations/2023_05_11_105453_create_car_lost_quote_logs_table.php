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
            $table->unsignedBigInteger('car_quote_request_id');
            $table->foreign('car_quote_request_id')->references('id')->on('car_quote_requests');
            $table->unsignedBigInteger('advisor_id');
            $table->integer('quote_status_id');
            $table->string('status', 30);// for mo
            $table->unsignedBigInteger('action_by_id')->nullable();
            $table->string('reason_id')->nullable();//lookups
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
