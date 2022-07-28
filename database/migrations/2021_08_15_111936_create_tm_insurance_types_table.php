<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTmInsuranceTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tm_insurance_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', '100');
            $table->string('text', '100');
            $table->string('text_ar', '100');
            $table->integer('sort_order');
            $table->boolean('is_active')->default('1');
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
        Schema::dropIfExists('tm_insurance_types');
    }
}
