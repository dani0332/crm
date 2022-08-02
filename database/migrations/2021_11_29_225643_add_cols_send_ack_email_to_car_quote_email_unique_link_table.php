<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColsSendAckEmailToCarQuoteEmailUniqueLinkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_email_unique_link', function (Blueprint $table) {
            if (! Schema::hasColumn('car_quote_email_unique_link', 'is_ack_email')) {
                $table->integer('is_ack_email')->default(0);
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
        Schema::table('car_quote_email_unique_link', function (Blueprint $table) {
            //
        });
    }
}
