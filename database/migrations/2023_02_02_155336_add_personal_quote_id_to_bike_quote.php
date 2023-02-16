<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPersonalQuoteIdToBikeQuote extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bike_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('bike_quote_request', 'personal_quote_id') && Schema::hasTable('personal_quotes')) {
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
        Schema::table('bike_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('bike_quote_request', 'personal_quote_id')) {
                $table->dropForeign('bike_quote_request_personal_quote_id_foreign');
                $table->dropColumn('personal_quote_id');
            }
        });
    }
}
