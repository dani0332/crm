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
        Schema::table('car_quote_request_detail', function (Blueprint $table) {
            $table->boolean('is_deleted')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('car_quote_request_detail', function (Blueprint $table) {
            $table->dropColumn('is_deleted');
        });
    }
};
