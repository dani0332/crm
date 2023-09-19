<?php

use App\Models\RenewalBatch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRenewalBatchSegmentUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // pivot table
        if (! Schema::hasTable('renewal_batch_segment_user')) {
            Schema::create('renewal_batch_segment_user', function (Blueprint $table) {
                $table->id();

                $table->foreignId('renewal_batch_id')
                    ->constrained('renewal_batches')
                    ->constrained('retention_configs')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->enum('segment_type', [RenewalBatch::SGEMENT_TYPES_LIST]);

                $table->foreignId('advisor_id')
                    ->constrained('users')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

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
        Schema::dropIfExists('renewal_batch_segment_user');
    }
}
