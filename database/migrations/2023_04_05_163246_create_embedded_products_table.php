<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmbeddedProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('embedded_products', function (Blueprint $table) {
            $table->id();
            $table->integer('insurance_provider_id')->nullable();
            $table->foreign('insurance_provider_id')->references('id')->on('insurance_provider');
            $table->string('company_name', '255')->nullable();
            $table->string('product_name', '255')->nullable();
            $table->string('short_code', '50')->nullable();
            $table->string('display_name', '255')->nullable();
            $table->string('product_type', '255')->nullable();
            $table->string('logic', '255')->nullable();
            $table->string('description', '255')->nullable();
            $table->string('description2', '255')->nullable();
            $table->string('commission_type', 100)->nullable();
            $table->string('commission_value', 100)->nullable();
            $table->string('email_template_id', 50)->nullable();
            $table->text('company_documents')->nullable();
            $table->text('removal_confirmation')->nullable();
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
        Schema::dropIfExists('embedded_products');
    }
}
