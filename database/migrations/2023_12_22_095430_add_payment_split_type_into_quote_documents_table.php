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
            if (! Schema::hasColumn('quote_documents', 'payment_split_type')) {
                $table->string('payment_split_type', 255)->nullable()->default(null);
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
