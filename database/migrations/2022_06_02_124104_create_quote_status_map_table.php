<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteStatusMapTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('quote_status_map')) {
            Schema::create('quote_status_map', function (Blueprint $table) {
                $table->id();
                $table->string('type_of_insurance', '150')->nullable();
                $table->integer('quote_status_id')->nullable();
                $table->integer('sort_order')->nullable();
                $table->string('created_by', '100')->nullable();
                $table->string('updated_by', '100')->nullable();
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
        Schema::dropIfExists('quote_status_map');
    }
}
