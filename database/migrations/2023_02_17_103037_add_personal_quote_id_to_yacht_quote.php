<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPersonalQuoteIdToYachtQuote extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('yacht_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('yacht_quote_request', 'personal_quote_id') && Schema::hasTable('personal_quotes')) {
                $table->unsignedBigInteger('personal_quote_id')->nullable();
                $table->foreign('personal_quote_id')->references('id')->on('personal_quotes');
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
        Schema::table('yacht_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('yacht_quote_request', 'personal_quote_id')) {
                $table->dropForeign('yacht_quote_request_personal_quote_id_foreign');
                $table->dropColumn('personal_quote_id');
            }
        });
    }
}
