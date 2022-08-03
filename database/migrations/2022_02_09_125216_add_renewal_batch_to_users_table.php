<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRenewalBatchToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('car_quote_request')) {
            Schema::table('car_quote_request', function (Blueprint $table) {
                if (! Schema::hasColumn('car_quote_request', 'renewal_batch')) {
                    $table->integer('renewal_batch')->nullable();
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
        if (Schema::hasTable('car_quote_request')) {
            Schema::table('car_quote_request', function (Blueprint $table) {
                if (! Schema::hasColumn('car_quote_request', 'renewal_batch')) {
                    $table->dropColumn('renewal_batch');
                }
            });
        }
    }
}
