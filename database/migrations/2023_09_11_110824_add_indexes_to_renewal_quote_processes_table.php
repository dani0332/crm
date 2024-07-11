<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToRenewalQuoteProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renewal_quote_processes', function (Blueprint $table) {

            $table->index('quote_type');
            $table->index('policy_number');
            $table->index('batch');
            $table->index('status');
            $table->index('fetch_plans_status');
            $table->index('email_sent');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
}
