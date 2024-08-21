<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPetQuoteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pet_quote_request', function (Blueprint $table) {
            $table->string('policy_number', 100)->nullable();
            $table->bigInteger('previous_quote_id')->nullable();
            $table->dateTime('policy_expiry_date')->nullable();
            $table->string('renewal_batch', 100)->nullable();
            $table->string('previous_quote_policy_number', 100)->nullable();
            $table->string('renewal_import_code', 100)->nullable();
            $table->date('previous_policy_expiry_date')->nullable();
            $table->decimal('previous_quote_policy_premium')->nullable();
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
            //
        });
    }
}
