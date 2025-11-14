<?php

namespace Database\Seeders;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\LookupsEnum;
use App\Models\InsuranceProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QICTokioLookupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $targetProvider = [
            InsuranceProvidersEnum::QIC,
            InsuranceProvidersEnum::TM,
        ];

        // Source provider is LIVA
        $LIVAInsurerProvider = InsuranceProvider::where('code', InsuranceProvidersEnum::RSA)->first();

        DB::transaction(function () use ($targetProvider, $LIVAInsurerProvider) {
            foreach ($targetProvider as $provider) {

                $targetProvider = InsuranceProvider::where('code', $provider)->first();
                // Fetch provider records
                $providerRecords = DB::table('lookups')
                    ->where('insurance_provider_id', $targetProvider->id)
                    ->whereIn('key', [LookupsEnum::PLATE_CODE, LookupsEnum::RTA_TRANSACTION_TYPE, LookupsEnum::BANK_NAME])
                    ->exists();

                // If provider records already exist, skip
                if ($providerRecords) {
                    continue;
                }

                // To add all rows at once rather than in loop
                DB::table('lookups')->insertUsing(
                    [
                        'quote_type_id',
                        'insurance_provider_id',
                        'key',
                        'code',
                        'text',
                        'parent_id',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                    DB::table('lookups')
                        ->selectRaw(
                            'quote_type_id, ? as insurance_provider_id, `key`, `code`, `text`, `parent_id`, `is_active`, NOW(), NOW()',
                            [$targetProvider->id]
                        )
                        ->where('insurance_provider_id', $LIVAInsurerProvider->id)
                        ->whereIn('key', [LookupsEnum::PLATE_CODE, LookupsEnum::RTA_TRANSACTION_TYPE,
                            LookupsEnum::BANK_NAME])
                );
            }
        });
    }
}
