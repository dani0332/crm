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
        Schema::create('broker_invoice_numbers', function (Blueprint $table) {
            $table->id();
            $table->integer('insurance_provider_id');
            $table->string('date')->comment('year month for broker invoice number');
            $table->unsignedInteger('sequence_number')->comment('current sequence number is unused will be updated once assigned to quote');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('insurance_provider_id')->references('id')->on('insurance_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broker_invoice_numbers');
    }
};
