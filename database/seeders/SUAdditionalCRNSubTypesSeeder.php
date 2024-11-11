<?php

namespace Database\Seeders;

use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\Lookup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SUAdditionalCRNSubTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parent = Lookup::where('code', SendUpdateLogStatusEnum::EF)->first();
        $quoteTypes = [
            QuoteTypeId::Car,
            QuoteTypeId::Home,
            QuoteTypeId::Health,
            QuoteTypeId::Life,
            QuoteTypeId::Business,
            QuoteTypeId::Bike,
            QuoteTypeId::Yacht,
            QuoteTypeId::Travel,
            QuoteTypeId::Pet,
            QuoteTypeId::Cycle,
        ];

        $endorsementsSubTypes = [
            SendUpdateLogStatusEnum::ATCRNB => [
                'key' => 'additional-tax-credit-note-booking',
                'text' => 'Additional tax credit note booking',
                'description' => 'Select this option to book any credit note related to a reduction in the premium amount only.',
            ],
            SendUpdateLogStatusEnum::ATCRNB_RBB => [
                'key' => 'additional-tax-credit-note-raised-by-buyer-booking',
                'text' => 'Additional tax credit note raised by buyer booking',
                'description' => 'Select this option to book any credit note related to a reduction in the commission amount only.',
            ],
            SendUpdateLogStatusEnum::ATCRN_CRNRBB => [
                'key' => 'additional-tax-credit-note-and-tax-credit-note-raised-by-buyer-booking',
                'text' => 'Additional tax credit note and tax credit note raised by buyer booking',
                'description' => 'Select this option to book any credit note related to a reduction in the premium & commission amount.',
            ],
        ];

        foreach ($quoteTypes as $quoteTypeId) {
            foreach ($endorsementsSubTypes as $endorsementCode => $endorsementDetails) {
                Lookup::firstOrCreate([
                    'quote_type_id' => $quoteTypeId,
                    'business_insurance_type_id' => null,
                    'key' => $endorsementDetails['key'],
                    'text' => $endorsementDetails['text'],
                    'code' => $endorsementCode,
                    'parent_id' => $parent->id,
                ], [
                    'description' => $endorsementDetails['description'],
                ]);
            }
        }
    }
}
