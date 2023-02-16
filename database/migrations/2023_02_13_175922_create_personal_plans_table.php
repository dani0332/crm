<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonalPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personal_plans', function (Blueprint $table) {
            $table->id();

            $table->integer('quote_type_id')->nullable(false);
            $table->foreign('quote_type_id')->references('id')->on('quote_type');

            $table->integer('insurance_provider_id')->nullable(false);
            $table->foreign('insurance_provider_id')->references('id')->on('insurance_provider');

            $table->string('code', 50)->nullable(false);
            $table->string('text', 100)->nullable(false);
            $table->string('text_ar', 100)->nullable();
            $table->boolean('is_active')->nullable()->default(0);

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
        Schema::dropIfExists('personal_plans');
    }
}
