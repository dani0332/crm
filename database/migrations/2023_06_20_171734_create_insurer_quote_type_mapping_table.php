<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsurerQuoteTypeMappingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insurer_quote_type_mapping', function (Blueprint $table) {
            $table->integer('quote_type_id');
            $table->foreign('quote_type_id')->references('id')->on('quote_type');

            $table->integer('insurance_provider_id');
            $table->foreign('insurance_provider_id')->references('id')->on('insurance_provider');
        });
    }
}
