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
        Schema::table('quote_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('quote_documents', 'payment_split_id')) {
                $table->unsignedBigInteger('payment_split_id')->nullable()->default(null);
                $table->foreign('payment_split_id')->references('id')->on('payment_splits')->onDelete('no action');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
