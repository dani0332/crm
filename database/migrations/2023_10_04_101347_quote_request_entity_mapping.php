<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('quote_request_entity_mapping')) {
            Schema::create('quote_request_entity_mapping', function (Blueprint $table) {
                $table->id();
                $table->integer('quote_type_id');
                $table->foreign('quote_type_id')->references('id')->on('quote_type');
                $table->integer('quote_request_id');
                $table->bigInteger('entity_id')->unsigned();
                $table->foreign('entity_id')->references('id')->on('entities');
                $table->string('entity_type_code')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
