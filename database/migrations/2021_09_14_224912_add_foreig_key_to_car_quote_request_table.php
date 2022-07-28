<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeigKeyToCarQuoteRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('car_quote_request', 'kyc_status')) {
                $table->dropColumn('kyc_status');
            }

            $table->bigInteger('kyc_status_id')->nullable()->unsigned();
            $table->foreign('kyc_status_id')->references('id')->on('kyc_statuses');
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
