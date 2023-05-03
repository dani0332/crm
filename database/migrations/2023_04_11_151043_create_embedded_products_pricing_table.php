<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmbeddedProductsPricingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('embedded_product_pricings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('embedded_product_id')->nullable();
            $table->foreign('embedded_product_id')->references('id')->on('embedded_products');

            $table->decimal('price', 10, 2)->nullable();
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
        Schema::dropIfExists('embedded_product_pricings');
    }
}
