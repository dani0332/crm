<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuoteIdToRenewalQuoteProcesses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renewal_quote_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('renewal_quote_processes', 'quote_id')) {
                $table->unsignedBigInteger('quote_id')->nullable(true)->after('quote_type');
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
