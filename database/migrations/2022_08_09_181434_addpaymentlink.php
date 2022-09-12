<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Addpaymentlink extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'payment_link')) {
                $table->string('payment_link', 255)->nullable()->default(null);
            }
            if (! Schema::hasColumn('payments', 'payment_link_created_at')) {
                $table->timestamp('payment_link_created_at')->nullable()->default(null);
            }
        });

        Schema::table('payment_status_log', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_status_log', 'payment_code')) {
                $table->string('payment_code', 15)->nullable()->default(null);
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
        //
    }
}
