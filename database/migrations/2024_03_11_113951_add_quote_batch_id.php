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
        $tables = [
            'personal_quotes',
            'home_quote_request',
            'health_quote_request',
            'life_quote_request',
            'business_quote_request',
            'bike_quote_request',
            'yacht_quote_request',
            'travel_quote_request',
            'pet_quote_request',
            'cycle_quote_request',
            'jetski_quote_request',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'quote_batch_id')) {
                    $table->unsignedBigInteger('quote_batch_id')->nullable();
                    $table->foreign('quote_batch_id')->references('id')->on('quote_batches')->onDelete('no action');
                }
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
