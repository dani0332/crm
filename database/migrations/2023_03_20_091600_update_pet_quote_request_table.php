<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePetQuoteRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pet_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('pet_quote_request', 'personal_quote_id')) {
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
        Schema::table('pet_quote_request', function (Blueprint $table) {
            $table->dropForeign(['personal_quote_id']);
            $table->dropColumn('personal_quote_id');
        });
    }
}
