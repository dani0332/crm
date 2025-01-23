<?php

namespace Database\Seeders;

use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\BusinessInsuranceType;
use App\Models\Lookup;
use Illuminate\Database\Seeder;

class SendUpdateAdditionalTaxInvoice extends Seeder
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

        foreach ($quoteTypes as $quoteTypeId) {
            Lookup::firstOrCreate([
                'quote_type_id' => $quoteTypeId,
                'business_insurance_type_id' => null,
                'key' => 'additional-tax-invoice-commission-booking',
                'text' => 'Additional tax invoice and tax invoice raised by buyer booking',
                'code' => SendUpdateLogStatusEnum::ATICB,
                'parent_id' => $parent->id,
            ], [
                'description' => 'Select this option when you need to book additional tax invoices and commission. This may involve collection of an additional premium amount, please check with the insurer.',
            ]);
        }

        $businessInsuranceTypes = [
            quoteBusinessTypeCode::groupMedical,
            quoteBusinessTypeCode::carFleet,
        ];

        foreach ($businessInsuranceTypes as $businessTypeCode) {
            $businessInsuranceTypeId = BusinessInsuranceType::where('code', $businessTypeCode)->first()->id;

            Lookup::firstOrCreate([
                'quote_type_id' => QuoteTypeId::Business,
                'business_insurance_type_id' => $businessInsuranceTypeId,
                'key' => 'additional-tax-invoice-commission-booking',
                'text' => 'Additional tax invoice and tax invoice raised by buyer booking',
                'code' => SendUpdateLogStatusEnum::ATICB,
                'parent_id' => $parent->id,
            ], [
                'description' => 'Select this option when you need to book additional tax invoices and commission. This may involve collection of an additional premium amount, please check with the insurer.',
            ]);
        }
    }
}
