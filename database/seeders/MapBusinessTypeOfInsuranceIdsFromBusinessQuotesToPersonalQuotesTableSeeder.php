<?php

namespace Database\Seeders;

use App\Enums\QuoteTypeId;
use App\Models\BusinessQuote;
use App\Models\PersonalQuote;
use Illuminate\Database\Seeder;

class MapBusinessTypeOfInsuranceIdsFromBusinessQuotesToPersonalQuotesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PersonalQuote::query()
            ->where('quote_type_id', QuoteTypeId::Business)
            ->whereNull('business_type_of_insurance_id')
            ->orderBy('id', 'desc')
            ->chunk(200, function ($personalQuotes) {
                foreach ($personalQuotes as $personalQuote) {
                    $subTypeId = BusinessQuote::query()
                        ->select('business_type_of_insurance_id')
                        ->where('code', $personalQuote->code)
                        ->whereNotNull('business_type_of_insurance_id')
                        ->first();

                    if ($subTypeId) {
                        $personalQuote->business_type_of_insurance_id =
                            $subTypeId->business_type_of_insurance_id;
                        $personalQuote->save();
                    }
                }
            });
    }
}
