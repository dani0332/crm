<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInslyDataMappingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('insly_data_mapping')) {
            Schema::create('insly_data_mapping', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('customer_email')->index();
                $table->string('customer_name')->index();
                $table->json('insly_data');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('insly_data_mapping');
    }
}
