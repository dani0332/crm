<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCycleQuoteRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cycle_quote_request', function (Blueprint $table) {

            $table->id();
            $table->integer('quote_type_id')->nullable(false);
            $table->foreign('quote_type_id')->references('id')->on('quote_type');

            $table->string('cycle_make', 50)->nullable();
            $table->string('cycle_model', 50)->nullable();

            $table->integer('year_of_manufacture_id')->nullable();
            $table->foreign('year_of_manufacture_id')->references('id')->on('year_of_manufacture');

            $table->string('accessories', 100)->nullable();
            $table->boolean('has_accident')->nullable();
            $table->boolean('has_good_condition')->nullable();

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
        Schema::dropIfExists('cycle_quote_request');
    }
}
