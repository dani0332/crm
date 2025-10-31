<?php

namespace Database\Seeders;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\LookupsEnum;
use App\Enums\QuoteTypeId;
use App\Models\InsuranceProvider;
use App\Models\Lookup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsurancePlateCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Target providers
        $insurerList = [
            InsuranceProvidersEnum::RSA,
            InsuranceProvidersEnum::RAK,
            InsuranceProvidersEnum::DNIRC,
            InsuranceProvidersEnum::AMJ,
            InsuranceProvidersEnum::FID,
            InsuranceProvidersEnum::NT,
            InsuranceProvidersEnum::AFNIC,
            InsuranceProvidersEnum::AWNI,
        ];

        // Fetch place codes data
        $plateCodes = require base_path('data/PlateCodes.php');

        foreach ($insurerList as $insurer) {
            $insurerProvider = InsuranceProvider::where('code', $insurer)->first();
            $insurerProviderRecords = Lookup::where('insurance_provider_id', $insurerProvider->id)
                ->where('key', LookupsEnum::PLATE_CODE)
                ->exists();

            // Skip if provider already has data
            if ($insurerProviderRecords) {
                continue;
            }

            $insertData = [];
            foreach ($plateCodes as $plate) {
                $insertData[] = [
                    'quote_type_id' => QuoteTypeId::Car,
                    'insurance_provider_id' => $insurerProvider->id,
                    'key' => 'plate-code',
                    'code' => $plate['code'],
                    'text' => $plate['text'],
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('lookups')->insert($insertData);
            echo "Plate codes inserted successfully for provider {$insurerProvider->code}.";
        }
    }
}
