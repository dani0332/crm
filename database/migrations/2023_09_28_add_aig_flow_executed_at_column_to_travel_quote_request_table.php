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
        Schema::table('travel_quote_request', function (Blueprint $table) {
            $table->timestamp('aig_flow_executed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_quote_request', function (Blueprint $table) {
            $table->dropColumn('aig_flow_executed_at');
        });
    }
}; 