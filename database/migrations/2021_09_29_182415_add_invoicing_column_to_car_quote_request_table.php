<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvoicingColumnToCarQuoteRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('car_quote_request', 'invoicing')) {
                $table->bigInteger('invoicing')->nullable()->unsigned();
                $table->index('invoicing');
                $table->foreign('invoicing')->references('id')->on('users');
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
        Schema::table('car_quote_request', function (Blueprint $table) {
            //
        });
    }
}
