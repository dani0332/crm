<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmbeddedProductsPlacementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('embedded_product_placements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('embedded_product_id')->nullable();
            $table->foreign('embedded_product_id')->references('id')->on('embedded_products');

            $table->integer('quote_type_id')->nullable();
            $table->foreign('quote_type_id')->references('id')->on('quote_type');

            $table->string('position', '255')->nullable();
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
        Schema::dropIfExists('embedded_product_placements');
    }
}
