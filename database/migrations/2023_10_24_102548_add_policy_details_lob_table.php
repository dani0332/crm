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
            Schema::table(strtolower($item).'_quote_request', function (Blueprint $table) use ($item) {
                if (! Schema::hasColumn(strtolower($item).'_quote_request', 'price_vat_not_applicable')) {
                    $table->decimal('price_vat_not_applicable', 10, 2)->nullable();
                }
                if (! Schema::hasColumn(strtolower($item).'_quote_request', 'price_without_vat')) {
                    $table->decimal('price_without_vat', 10, 2)->nullable();
                }
                if (! Schema::hasColumn(strtolower($item).'_quote_request', 'price_with_vat')) {
                    $table->decimal('price_with_vat', 10, 2)->nullable();
                }
                if (! Schema::hasColumn(strtolower($item).'_quote_request', 'vat')) {
                    $table->decimal('vat', 10, 2)->nullable();
                }
                if (! Schema::hasColumn(strtolower($item).'_quote_request', 'insurer_quote_number')) {
                    $table->integer('insurer_quote_number')->nullable();
                }
                if (! Schema::hasColumn(strtolower($item).'_quote_request', 'policy_issuance_status_id')) {
                    $table->unsignedBigInteger('policy_issuance_status_id')->nullable();
                    $table->foreign('policy_issuance_status_id')->references('id')->on('policy_issuance_status');
                }
                if (! Schema::hasColumn(strtolower($item).'_quote_request', 'policy_issuance_status_other')) {
                    $table->text('policy_issuance_status_other')->nullable();
                }
            });
        }

        if (Schema::hasTable('personal_quotes')) {
            Schema::table('personal_quotes', function ($table) {
                if (! Schema::hasColumn('personal_quotes', 'price_vat_not_applicable')) {
                    $table->decimal('price_vat_not_applicable', 10, 2)->nullable();
                }
                if (! Schema::hasColumn('personal_quotes', 'price_without_vat')) {
                    $table->decimal('price_without_vat', 10, 2)->nullable();
                }
                if (! Schema::hasColumn('personal_quotes', 'price_with_vat')) {
                    $table->decimal('price_with_vat', 10, 2)->nullable();
                }
                if (! Schema::hasColumn('personal_quotes', 'vat')) {
                    $table->decimal('vat', 10, 2)->nullable();
                }
                if (! Schema::hasColumn('personal_quotes', 'insurer_quote_number')) {
                    $table->integer('insurer_quote_number')->nullable();
                }
                if (! Schema::hasColumn('personal_quotes', 'policy_issuance_status_id')) {
                    $table->unsignedBigInteger('policy_issuance_status_id')->nullable();
                    $table->foreign('policy_issuance_status_id')->references('id')->on('policy_issuance_status');
                }
                if (! Schema::hasColumn('personal_quotes', 'policy_issuance_status_other')) {
                    $table->text('policy_issuance_status_other')->nullable();
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
            Schema::table(strtolower($item).'_quote_request', function (Blueprint $table) {
                $table->dropColumn('price_vat_not_applicable');
                $table->dropColumn('price_without_vat');
                $table->dropColumn('price_with_vat');
                $table->dropColumn('vat');
                $table->dropColumn('insurer_quote_number');

                $table->dropForeign(['policy_issuance_status_id']);
                $table->dropColumn('policy_issuance_status_id');

                $table->dropColumn('policy_issuance_status_other');
            });
        }
        Schema::table('personal_quotes', function (Blueprint $table) {
            $table->dropForeign(['policy_issuance_status_id']);
            $table->dropColumn('policy_issuance_status_id');

            $table->dropColumn('insurer_quote_number');
            $table->dropColumn('vat');
            $table->dropColumn('price_with_vat');
            $table->dropColumn('price_without_vat');
            $table->dropColumn('price_vat_not_applicable');
            $table->dropColumn('policy_issuance_status_other');
        });
    }
};
