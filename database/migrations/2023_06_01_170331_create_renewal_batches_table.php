<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRenewalBatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('renewal_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('quote_status_id');
            $table->foreign('quote_status_id')->references('id')->on('quote_status');

            $table->date('deadline_date');
            $table->timestamps();
        });
    }

}
