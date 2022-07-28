<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRenewalsBatchEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('renewals_batch_emails')) {
            Schema::create('renewals_batch_emails', function (Blueprint $table) {
                $table->id();
                $table->integer('batch')->nullable();
                $table->integer('total_leads')->nullable();
                $table->integer('total_sent')->nullable();
                $table->integer('total_bounced')->nullable();
                $table->string('status', '20')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->foreign('created_by_id')->references('id')->on('users')->onDelete('no action');
                $table->timestamps();
                $table->index('batch', 'idx_batch');
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
        Schema::dropIfExists('renewals_batch_emails');
    }
}
