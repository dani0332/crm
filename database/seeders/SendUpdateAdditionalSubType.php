<?php

namespace Database\Seeders;

use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\BusinessInsuranceType;
use App\Models\Lookup;
use Illuminate\Database\Seeder;

class SendUpdateAdditionalSubType extends Seeder
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
                'key' => 'correction-and-amendments-with-financial-effect',
                'text' => 'Correction and Amendments (with Financial Effect)',
                'code' => SendUpdateLogStatusEnum::CAAFE,
                'parent_id' => $parent->id,
            ], [
                'description' => 'This endorsement allows for adjustments to the policy that have a financial impact, such as changes to the insured amount or coverage details. Any changes affecting the policy’s financial terms may require an additional premium or credit adjustment. Please consult with the insurer to confirm any cost implications associated with this endorsement.',
            ]);

            if ($quoteTypeId != QuoteTypeId::Home) {
                Lookup::firstOrCreate([
                    'quote_type_id' => $quoteTypeId,
                    'business_insurance_type_id' => null,
                    'key' => 'decrease-sum-insured',
                    'text' => 'Decrease the sum insured',
                    'code' => SendUpdateLogStatusEnum::DTSI,
                    'parent_id' => $parent->id,
                ], [
                    'description' => 'Choose this option if you wish to reduce the overall amount for which your home is covered. This could be in scenarios where certain insured items are no longer in possession or if the property value has depreciated.',
                ]);
            }
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
                'key' => 'correction-and-amendments-with-financial-effect',
                'text' => 'Correction and Amendments (with Financial Effect)',
                'code' => SendUpdateLogStatusEnum::CAAFE,
                'parent_id' => $parent->id,
            ], [
                'description' => 'This endorsement allows for adjustments to the policy that have a financial impact, such as changes to the insured amount or coverage details. Any changes affecting the policy’s financial terms may require an additional premium or credit adjustment. Please consult with the insurer to confirm any cost implications associated with this endorsement.',
            ]);

            Lookup::firstOrCreate([
                'quote_type_id' => QuoteTypeId::Business,
                'business_insurance_type_id' => $businessInsuranceTypeId,
                'key' => 'decrease-sum-insured',
                'text' => 'Decrease the sum insured',
                'code' => SendUpdateLogStatusEnum::DTSI,
                'parent_id' => $parent->id,
            ], [
                'description' => 'Choose this option if you wish to reduce the overall amount for which your home is covered. This could be in scenarios where certain insured items are no longer in possession or if the property value has depreciated.',
            ]);
        }

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Travel,
            'key' => 'midterm-policy-cancellation',
            'text' => 'Midterm policy cancellation',
            'code' => SendUpdateLogStatusEnum::MPC,
            'parent_id' => $parent->id,
        ], [
            'description' => 'This option allows policyholders to terminate their insurance before its scheduled end date. Common reasons include leaving the country, obtaining a new insurance policy elsewhere (e.g., a new employer), or the premium payments has lapsed from the policyholder. Always confirm the reason before processing.',
        ]);
    }
}
