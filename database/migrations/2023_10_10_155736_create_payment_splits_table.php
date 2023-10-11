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
        Schema::create('payment_splits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 15);
            $table->string('sr_no', 15);
            $table->string('payment_method', 50);
            $table->string('check_detail', 500)->nullable();
            $table->float('payment_amount', 16, 2);
            $table->dateTime('due_date');
            $table->integer('payment_status_id');
            $table->timestamps();
            $table->foreign('code')->references('code')->on('payments')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_splits');
    }
};
