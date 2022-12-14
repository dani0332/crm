<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRenewalQuoteProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('renewal_quote_processes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('renewals_upload_lead_id');
            $table->string('quote_type')->nullable(true);
            $table->string('policy_number')->nullable(true);
            $table->json('data')->nullable(true);
            $table->string('batch')->nullable(true);
            $table->json('validation_errors')->nullable(true);
            $table->string('status')->comment('new,validation_failed,validated,processed,email_sent')->default(0);
            $table->tinyInteger('email_sent')->unsigned()->default(0);
            $table->string('type')->comment('create or update');
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
        Schema::dropIfExists('renewal_quote_processes');
    }
}
