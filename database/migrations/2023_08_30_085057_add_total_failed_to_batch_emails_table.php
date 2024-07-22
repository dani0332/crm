<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddTotalFailedToBatchEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renewals_batch_emails', function ($table) {
            if (! Schema::hasColumn('renewals_batch_emails', 'total_failed')) {
                $table->integer('total_failed')->nullable()->default(null)->after('total_sent');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
}
