<?php

namespace Database\Seeders;

use App\Enums\BranchEnum;
use App\Enums\QuoteTypes;
use App\Models\BranchOverrideConfig;
use Illuminate\Database\Seeder;

class BranchOverrideConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates branch override configurations for redirecting quotes from
     * Abu Dhabi to Dubai across all quote types.
     */
    public function run(): void
    {
        // Quote types that need branch override configuration
        $quoteTypes = [
            QuoteTypes::CAR,
            QuoteTypes::HOME,
            QuoteTypes::LIFE,
            QuoteTypes::BIKE,
            QuoteTypes::YACHT,
            QuoteTypes::TRAVEL,
            QuoteTypes::PET,
            QuoteTypes::CYCLE,
            QuoteTypes::JETSKI,
            QuoteTypes::BUSINESS,
            QuoteTypes::SAVINGS,
            QuoteTypes::CYBER,
            QuoteTypes::DEVICE,
        ];

        foreach ($quoteTypes as $quoteType) {
            BranchOverrideConfig::firstOrCreate(
                [
                    'source_branch_id' => BranchEnum::ABU_DHABI->value,
                    'target_branch_id' => BranchEnum::DUBAI->value,
                    'quote_type_id' => $quoteType->id(),
                ],
                [
                    'start_date' => now(),
                    'override_text' => 'TEMP_AUH_TO_DXB',
                    'created_by' => 'system_admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
