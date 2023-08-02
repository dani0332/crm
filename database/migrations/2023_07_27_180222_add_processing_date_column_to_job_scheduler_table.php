<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProcessingDateColumnToJobSchedulerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_scheduler', function (Blueprint $table) {
            if (! Schema::hasColumn('job_scheduler', 'processing_date')) {
                $table->timestamp('processing_date')->nullable();
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
        Schema::table('job_scheduler', function (Blueprint $table) {
            $table->dropColumn('processing_date');
        });
    }
}
