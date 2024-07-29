<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSkipPlansToRenewalsUpload extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renewals_upload_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('renewals_upload_leads', 'skip_plans')) {
                $table->tinyInteger('skip_plans')->nullable()->default(0);
            }
        });

        Schema::table('renewal_status_processes', function (Blueprint $table) {
            if (Schema::hasColumn('renewal_status_processes', 'skip_plans')) {
                $table->dropColumn('skip_plans');
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
