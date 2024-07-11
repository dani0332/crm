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
        Schema::create('cc_payment_process_jobs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('payment_split_id')->nullable();
            $table->foreign('payment_split_id')->references('id')->on('payment_splits');            
            $table->string('model_type','15');
            $table->bigInteger('quote_id');
            $table->bigInteger('send_update_id')->nullable();
            $table->float('amount_captured', 16, 2);
            $table->string('status','10')->default('pending');
            $table->string('message','500')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cc_payment_process_jobs');
    }
};
