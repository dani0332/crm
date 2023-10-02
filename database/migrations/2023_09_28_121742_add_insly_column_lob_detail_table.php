<?php

use App\Enums\QuoteTypes;
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
        $lobs = [
            QuoteTypes::CAR->value,
            QuoteTypes::HEALTH->value,
            QuoteTypes::TRAVEL->value,
            QuoteTypes::LIFE->value,
            QuoteTypes::HOME->value,
            QuoteTypes::BUSINESS->value,
        ];

        foreach ($lobs as $item) {
            Schema::table(strtolower($item) . '_quote_request_detail', function (Blueprint $table) use ($item) {
                if (!Schema::hasColumn(strtolower($item) . '_quote_request_detail', 'insly_id')) {
                    $table->string('insly_id', 50)->nullable();
                }
            });
        }

        if (Schema::hasTable('personal_quote_details')) {
            Schema::table('personal_quote_details', function ($table) {
                if (!Schema::hasColumn('personal_quote_details', 'insly_id')) {
                    $table->string('insly_id', 50)->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $lobs = [
            QuoteTypes::CAR->value,
            QuoteTypes::HEALTH->value,
            QuoteTypes::TRAVEL->value,
            QuoteTypes::LIFE->value,
            QuoteTypes::HOME->value,
            QuoteTypes::BUSINESS->value,
        ];

        foreach ($lobs as $item) {
            Schema::table(strtolower($item) . '_quote_request_detail', function (Blueprint $table) {
                $table->dropColumn('insly_id');
            });
        }

        Schema::table('personal_quote_details', function (Blueprint $table) {
            $table->dropColumn('insly_id');
        });
    }
};
