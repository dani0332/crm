<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdvisorPaFieldsToCarQuoteRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_request', function (Blueprint $table) {
            $table->smallInteger('kyc_status')->default(0);
            $table->bigInteger('advisor_id')->nullable()->unsigned();
            $table->index('advisor_id');
            $table->foreign('advisor_id')->references('id')->on('users');
            $table->bigInteger('pa_id')->nullable()->unsigned();
            $table->index('pa_id');
            $table->foreign('pa_id')->references('id')->on('users');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('car_quote_request', function (Blueprint $table) {
            //
        });
    }
}
