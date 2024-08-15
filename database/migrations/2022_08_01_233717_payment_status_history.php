<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PaymentStatusHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_status_log', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_status_log', 'payment_code')) {
                $table->string('payment_code', 15);
            }
            if (! Schema::hasColumn('payment_status_log', 'modifier')) {
                $table->unsignedBigInteger('modifier')->nullable();
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
