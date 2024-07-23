<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeMinMaxColumnTypeInRenewalBatchSlabsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renewal_batch_slabs', function (Blueprint $table) {
            if (Schema::hasColumn('renewal_batch_slabs', 'max')) {
                $table->float('max', 5, 2)->change()->default(0.0);
            }

            if (Schema::hasColumn('renewal_batch_slabs', 'min')) {
                $table->float('min', 5, 2)->change()->default(0.0);
            }

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renewal_batch_slabs', function (Blueprint $table) {});
    }
}
