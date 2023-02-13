<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonalQuoteDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personal_quote_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_quote_id');
            $table->foreign('personal_quote_id')->references('id')->on('personal_quotes');

            $table->unsignedBigInteger('previous_advisor_id')->nullable();
            $table->foreign('previous_advisor_id')->references('id')->on('users');

            $table->unsignedBigInteger('pa_id')->nullable();
            $table->foreign('pa_id')->references('id')->on('users');

            $table->string('reviver_name', 100)->nullable();
            $table->date('advisor_assigned_date')->nullable();
            $table->unsignedBigInteger('advisor_assigned_by_id')->nullable();
            $table->foreign('advisor_assigned_by_id')->references('id')->on('users');

            $table->date('next_followup_date')->nullable();
            $table->bigInteger('lost_reason_id')->nullable();
            $table->foreign('lost_reason_id')->references('id')->on('lost_reasons');

            $table->string('transapp_code')->nullable();
            $table->string('additional_notes', 500)->nullable();
            $table->string('utm_source', 50)->nullable();
            $table->string('utm_medium', 50)->nullable();
            $table->string('utm_campaign', 50)->nullable();

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('personal_quote_details');
    }
}
