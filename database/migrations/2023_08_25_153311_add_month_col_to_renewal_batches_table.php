<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMonthColToRenewalBatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renewal_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('renewal_batches', 'month')) {
                $table->enum('month', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12])
                    ->after('end_date')
                    ->nullable()
                    ->default(null);
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
        Schema::table('renewal_batches', function (Blueprint $table) {
            if (Schema::hasColumn('renewal_batches', 'month')) {
                $table->dropColumn('month');

            }
        });
    }
}
