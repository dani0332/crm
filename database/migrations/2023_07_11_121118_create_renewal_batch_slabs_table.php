<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRenewalBatchSlabsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // pivot table
        if (! Schema::hasTable('renewal_batch_slabs')) {
            Schema::create('renewal_batch_slabs', function (Blueprint $table) {
                $table->id();

                $table->foreignId('renewal_batch_id')
                    ->constrained('renewal_batches')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->foreignId('team_id')
                    ->constrained('teams')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->foreignId('slab_id')
                    ->constrained('slabs')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->integer('max')->default(0);
                $table->integer('min')->default(0);

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
        Schema::dropIfExists('renewal_batch_slabs');
    }
}
