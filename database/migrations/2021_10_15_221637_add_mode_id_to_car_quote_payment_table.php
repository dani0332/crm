<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddModeIdToCarQuotePaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_payment', function (Blueprint $table) {
            if (! Schema::hasColumn('car_quote_payment', 'mode_id')) {
                $table->integer('mode_id');
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
        Schema::table('car_quote_payment', function (Blueprint $table) {
            //
        });
    }
}
