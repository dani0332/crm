<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TemporaryData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('temp_data')) {
            Schema::create('temp_data', function (Blueprint $table) {
                $table->id();
                $table->string('quote_type', 255)->nullable();
                $table->string('quote_uuid')->nullable();
                $table->string('form')->nullable();
                $table->longText('values')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->foreign('created_by_id')->references('id')->on('users')->onDelete('no action');
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
        Schema::dropIfExists('temp_data');
    }
}
