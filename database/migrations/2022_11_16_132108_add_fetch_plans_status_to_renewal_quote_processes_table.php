<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFetchPlansStatusToRenewalQuoteProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renewal_quote_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('renewal_quote_processes', 'fetch_plans_status')) {
                $table->string('fetch_plans_status', 50)->nullable(true)->default(null)->after('status');
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
