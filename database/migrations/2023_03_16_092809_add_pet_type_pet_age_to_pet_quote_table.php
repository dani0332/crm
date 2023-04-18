<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPetTypePetAgeToPetQuoteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pet_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('pet_quote_request', 'pet_type_id')) {
                $table->unsignedBigInteger('pet_type_id')->nullable();
                $table->foreign('pet_type_id')->references('id')->on('lookups');
            }
            if (! Schema::hasColumn('pet_quote_request', 'pet_age_id')) {
                $table->unsignedBigInteger('pet_age_id')->nullable();
                $table->foreign('pet_age_id')->references('id')->on('lookups');
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
            $table->dropForeign(['pet_type_id']);
            $table->dropColumn('pet_type_id');

            $table->dropForeign(['pet_age_id']);
            $table->dropColumn('pet_age_id');
        });
    }
}
