<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCdbIdToQuoteCustomers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('quote_customers')) {
            Schema::table('quote_customers', function (Blueprint $table) {
                if (Schema::hasColumn('quote_customers', 'cdb_id')) {
                    $table->string('cdb_id', 30)->change();
                }
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
        Schema::table('quote_customers', function (Blueprint $table) {
            //
        });
    }
}
