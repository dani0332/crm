<?php

namespace Database\Seeders;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteTypes;
use App\Models\InsuranceProvider;
use Illuminate\Database\Seeder;

class InsuranceQuoteTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        InsuranceProvider::updateOrCreate(['code' => InsuranceProvidersEnum::FPIL], ['text' => 'Friends Provident International Limited', 'text_lms' => 'Friends Provident International Limited']);
        InsuranceProvider::updateOrCreate(['code' => InsuranceProvidersEnum::ZILL], ['text' => 'Zurich International Life Limited', 'text_lms' => 'Zurich International Life Limited']);
        InsuranceProvider::updateOrCreate(['code' => InsuranceProvidersEnum::STF], ['text' => 'Sukoon Takaful', 'text_lms' => 'Sukoon Takaful']);
        InsuranceProvider::updateOrCreate(['code' => InsuranceProvidersEnum::AIAW], ['text' => 'Al Ittihad al Watania', 'text_lms' => 'Al Ittihad al Watania']);

        $quoteTypes = [
            ['quote_type_id' => QuoteTypes::LIFE->id(), 'providers' => [
                InsuranceProvidersEnum::FPIL, InsuranceProvidersEnum::ZILL, InsuranceProvidersEnum::MTL, InsuranceProvidersEnum::OI2,
                InsuranceProvidersEnum::SI, InsuranceProvidersEnum::OIC,
            ]],
            ['quote_type_id' => QuoteTypes::PET->id(), 'providers' => [
                InsuranceProvidersEnum::AIAW,
            ]],
            ['quote_type_id' => QuoteTypes::YACHT->id(), 'providers' => [
                InsuranceProvidersEnum::ALJALIL, InsuranceProvidersEnum::AXA, InsuranceProvidersEnum::OI2, InsuranceProvidersEnum::OUNB,
                InsuranceProvidersEnum::QIC, InsuranceProvidersEnum::OIC, InsuranceProvidersEnum::STF, InsuranceProvidersEnum::UI,
            ]],
            ['quote_type_id' => QuoteTypes::CORPLINE->id(), 'providers' => [
                InsuranceProvidersEnum::AIG, InsuranceProvidersEnum::AIAW, InsuranceProvidersEnum::ARAB,
            ]],
        ];

        foreach ($quoteTypes as $quoteType) {
            $quoteTypeId = $quoteType['quote_type_id'];
            $providers = $quoteType['providers'];

            foreach ($providers as $provider) {
                if ($provider = InsuranceProvider::where('code', $provider)->first()) {
                    $provider->quoteTypes()->syncWithoutDetaching([$quoteTypeId]);
                }
            }
        }
    }
}
