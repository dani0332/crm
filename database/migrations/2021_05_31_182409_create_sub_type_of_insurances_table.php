<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubTypeOfInsurancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_type_of_insurances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('text', '255');
            $table->string('text_ar', '255');
            $table->boolean('is_active')->default('1');
            $table->integer('sort_order');
            $table->boolean('is_deleted')->default('0');
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
        Schema::dropIfExists('sub_type_of_insurances');
    }
}
