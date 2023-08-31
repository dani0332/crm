<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRenewalBatchDeadlinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('renewal_batch_deadlines')) {
            Schema::create('renewal_batch_deadlines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('renewal_batch_id')
                    ->constrained('renewal_batches')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();

                $table->integer('quote_status_id');
                $table->foreign('quote_status_id')->references('id')->on('quote_status');
                $table->date('deadline_date');
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
        Schema::dropIfExists('renewal_batch_deadlines');
    }
}
