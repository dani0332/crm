<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarRepairCoveragesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('car_repair_coverages', function (Blueprint $table) {
            $table->id();
            $table->string('text','255');
            $table->string('text_ar','255');
            $table->tinyInteger('is_active','4')->default('1');
            $table->integer('sort_order','11');
            $table->tinyInteger('is_deleted','4')->default('0');
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
        Schema::dropIfExists('car_repair_coverages');
    }
}
