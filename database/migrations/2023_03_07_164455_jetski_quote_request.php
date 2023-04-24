<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JetskiQuoteRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jetski_quote_request', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_quote_id')->nullable(false);
            $table->foreign('personal_quote_id')->references('id')->on('personal_quotes');

            $table->string('jetski_make', 50)->nullable();
            $table->string('jetski_model', 50)->nullable();
            $table->integer('year_of_manufacture_id')->nullable();
            $table->foreign('year_of_manufacture_id')->references('id')->on('year_of_manufacture');

            $table->string('max_speed', 50)->nullable();
            $table->string('seat_capacity', 50)->nullable();
            $table->string('engine_power', 50)->nullable();

            $table->unsignedBigInteger('jetski_material_id')->nullable();
            $table->foreign('jetski_material_id')->references('id')->on('lookups');

            $table->unsignedBigInteger('jetski_use_id')->nullable();
            $table->foreign('jetski_use_id')->references('id')->on('lookups');

            $table->string('claim_history', 50)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jetski_quote_request');
    }
}
