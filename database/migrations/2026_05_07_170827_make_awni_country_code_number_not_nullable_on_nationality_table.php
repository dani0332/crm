<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Backfills null or empty values, then enforces NOT NULL with default 0 for unmapped rows.
     */
    public function up(): void
    {
        if (! Schema::hasTable('nationality') || ! Schema::hasColumn('nationality', 'awni_country_code_number')) {
            return;
        }

        DB::table('nationality')
            ->where(function ($query): void {
                $query->whereNull('awni_country_code_number')
                    ->orWhere('awni_country_code_number', '');
            })
            ->update(['awni_country_code_number' => 0]);

        Schema::table('nationality', function (Blueprint $table): void {
            $table->unsignedInteger('awni_country_code_number')->default(0)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('nationality') || ! Schema::hasColumn('nationality', 'awni_country_code_number')) {
            return;
        }

        Schema::table('nationality', function (Blueprint $table): void {
            $table->unsignedInteger('awni_country_code_number')->nullable()->change();
        });
    }
};
