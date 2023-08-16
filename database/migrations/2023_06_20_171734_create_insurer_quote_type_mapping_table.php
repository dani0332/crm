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
            $table->id();
            $table->integer('quote_type_id');
            $table->integer('insurance_provider_id');
        });
    }
}
