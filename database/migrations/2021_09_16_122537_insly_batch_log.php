<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InslyBatchLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('insly_batch_log')) {
            Schema::create('insly_batch_log', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('batch_start_date');
                $table->string('batch_end_date');
                $table->string('batch_records_processed')->index();
                $table->timestamps();
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
        //
    }
}
