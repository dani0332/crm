<?php

namespace Database\Seeders;

use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\BusinessInsuranceType;
use App\Models\Lookup;
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

        $businessInsuranceTypes = [
            quoteBusinessTypeCode::groupMedical,
            quoteBusinessTypeCode::carFleet,
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

            if ($quoteTypeId == QuoteTypeId::Business) {
                foreach ($businessInsuranceTypes as $businessTypeCode) {
                    $businessInsuranceTypeId = BusinessInsuranceType::where('code', $businessTypeCode)->first()->id;

                    foreach ($endorsementsSubTypes as $endorsementCode => $endorsementDetails) {
                        Lookup::firstOrCreate([
                            'quote_type_id' => QuoteTypeId::Business,
                            'business_insurance_type_id' => $businessInsuranceTypeId,
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

            //        The same seeder is being used to rename some of the already created send update subtypes.
            $getSendUpdateSubTypes = Lookup::where([
                'quote_type_id' => $quoteTypeId,
                'parent_id' => $parent->id,
            ])->whereIn('code', [SendUpdateLogStatusEnum::ACB, SendUpdateLogStatusEnum::ATICB])->get();

            foreach ($getSendUpdateSubTypes as $getSendUpdateSubType) {
                if ($getSendUpdateSubType->code == SendUpdateLogStatusEnum::ACB) {
                    $getSendUpdateSubType->text = 'Additional tax invoice raised by buyer booking';
                    $getSendUpdateSubType->description = 'Select this option to record additional commission tax invoices. This helps ensure accurate financial records and facilitates proper commission tracking.';

                    if ($getSendUpdateSubType->isDirty(['text', 'description'])) {
                        $getSendUpdateSubType->save();
                    }
                } elseif ($getSendUpdateSubType->code == SendUpdateLogStatusEnum::ATICB) {
                    $getSendUpdateSubType->text = 'Additional tax invoice and tax invoice raised by buyer booking';
                    $getSendUpdateSubType->description = 'Select this option when you need to book additional tax invoices and commission. This may involve collection of an additional premium amount, please check with the insurer.';

                    if ($getSendUpdateSubType->isDirty(['text', 'description'])) {
                        $getSendUpdateSubType->save();
                    }
                }
            }
        }
    }
}
