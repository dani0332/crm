<?php

namespace Database\Seeders;

use App\Enums\LookupsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\Lookup;
use Illuminate\Database\Seeder;

class LookupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->sendUpdateCancelOptions();
        $this->createEndorsementFinancialSavings();
        $this->createEndorsementNonFinancialSavings();
        $this->createCIRSavings();
        $this->createCISavings();
        $this->createRmCategories();
        $this->createReferralSources();
    }

    private function sendUpdateCancelOptions(): void
    {
        Lookup::firstOrCreate([
            'key' => LookupsEnum::SEND_UPDATE_CANCEL_OPTIONS,
            'code' => 'requested-by-mistake',
            'text' => 'Requested by mistake',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'key' => LookupsEnum::SEND_UPDATE_CANCEL_OPTIONS,
            'code' => 'selected-wrong-update',
            'text' => 'Selected the wrong update',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'key' => LookupsEnum::SEND_UPDATE_CANCEL_OPTIONS,
            'code' => 'update-no-longer-needed',
            'text' => 'Update no longer needed',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createEndorsementFinancialSavings(): void
    {
        $ef = Lookup::where('code', SendUpdateLogStatusEnum::EF)->first();

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'midterm-policy-cancellation',
            'code' => 'MPC',
            'text' => 'Midterm policy cancellation',
        ], [
            'parent_id' => $ef->id,
            'description' => 'This option allows policyholders to terminate their insurance before its scheduled end date. Common reasons include leaving the country, obtaining a new insurance policy elsewhere (e.g., a new employer), or the premium payments has lapsed from the policyholder. Always confirm the reason before processing.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'reinstatement',
            'code' => 'R',
            'text' => 'Reinstatement',
        ], [
            'parent_id' => $ef->id,
            'description' => 'Reinstatement refers to the act of bringing a lapsed or suspended life insurance policy back into active status. Ensure all conditions are met and necessary documentation is provided before proceeding with the reinstatement process. Be aware that additional payments may be required to fully reinstate the policy.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'additional-commission-booking',
            'code' => 'ACB',
            'text' => 'Additional tax invoice raised by buyer booking',
        ], [
            'parent_id' => $ef->id,
            'description' => 'Select this option to record additional commission tax invoices. This helps ensure accurate financial records and facilitates proper commission tracking.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'additional-tax-invoice-booking',
            'code' => 'ATIB',
            'text' => 'Additional tax invoice booking',
        ], [
            'parent_id' => $ef->id,
            'description' => '',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'additional-tax-invoice-commission-booking',
            'code' => 'ATICB',
            'text' => 'Additional tax invoice and tax invoice raised by buyer booking',
        ], [
            'parent_id' => $ef->id,
            'description' => 'Select this option when you need to book additional tax invoices and commission. This may involve collection of an additional premium amount, please check with the insurer.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'additional-tax-credit-note-booking',
            'code' => 'ATCRNB',
            'text' => 'Additional tax credit note booking',
        ], [
            'parent_id' => $ef->id,
            'description' => 'Select this option to book any credit note related to a reduction in the premium amount only.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'additional-tax-credit-note-raised-by-buyer-booking',
            'code' => 'ATCRNB_RBB',
            'text' => 'Additional tax credit note raised by buyer booking',
        ], [
            'parent_id' => $ef->id,
            'description' => 'Select this option to book any credit note related to a reduction in the commission amount only.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'additional-tax-credit-note-and-tax-credit-note-raised-by-buyer-booking',
            'code' => 'ATCRN_CRNRBB',
            'text' => 'Additional tax credit note and tax credit note raised by buyer booking',
        ], [
            'parent_id' => $ef->id,
            'description' => 'Select this option to book any credit note related to a reduction in the premium & commission amount.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'correction-and-amendments-with-financial-effect',
            'code' => 'CAAFE',
            'text' => 'Correction and Amendments (with Financial Effect)',
        ], [
            'parent_id' => $ef->id,
            'description' => 'This endorsement allows for adjustments to the policy that have a financial impact, such as changes to the insured amount or coverage details. Any changes affecting the policy\'s financial terms may require an additional premium or credit adjustment. Please consult with the insurer to confirm any cost implications associated with this endorsement.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'decrease-sum-insured',
            'code' => 'DTSI',
            'text' => 'Decrease the sum insured',
        ], [
            'parent_id' => $ef->id,
            'description' => 'Choose this option if you wish to reduce the overall amount for which your home is covered. This could be in scenarios where certain insured items are no longer in possession or if the property value has depreciated.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createEndorsementNonFinancialSavings(): void
    {
        $en = Lookup::where('code', SendUpdateLogStatusEnum::EN)->first();

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'correction-amendments',
            'code' => 'CAA',
            'text' => 'Correction and amendments',
        ], [
            'parent_id' => $en->id,
            'description' => 'To modify particular details or correct any inaccuracies within the policy records supplied by the insurer, these are the fields that can be updated without any financial implications. If there is a requirement to change customer name, policy number, start date, or end date in IMCRM, kindly reach out to the finance team to rectify these policy details in the IMCRM system after sending the update to the customer.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCIRSavings(): void
    {
        $cir = Lookup::where('code', SendUpdateLogStatusEnum::CIR)->first();

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'change-inception-date',
            'code' => 'CIID',
            'text' => 'Change in inception date',
        ], [
            'parent_id' => $cir->id,
            'description' => 'Requires cancellation and reissuance of the policy due to a change in the policy\'s start date.
            Example: Policy is issued with the start date as of today. Client has gotten back to us to request a change in the start date to a later date (future date) because of any reason, like, they have an existing policy until then.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'change-expiry-extension',
            'code' => 'CIED_EOP',
            'text' => 'Change in expiry date / Extension of policy',
        ], [
            'parent_id' => $cir->id,
            'description' => 'Requires cancellation and re-issuance due to a policy\'s expiry date change.
            Example: Travel date extension of a trip, which has the same start date however, extension is made to the end date of the trip.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'change-insurer',
            'code' => 'CII',
            'text' => 'Change in insurer',
        ], [
            'parent_id' => $cir->id,
            'description' => 'Requires cancellation and re-issuance due to a change of provider.
            Example: Client still needs to receive the benefits of the chosen insurer and hence wants to change their Insurer due to a delay. This change may involve a debit amount due or credit to the client.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'change-cover',
            'code' => 'CIC',
            'text' => 'Change in cover',
        ], [
            'parent_id' => $cir->id,
            'description' => 'Requires cancellation and re-issuance due to a change of cover.
            Example: Policy is not yet started, and the client now wants to add a cover, this would involve a cancellation of the current policy and re-issuance of the new policy with the cover(s) added accordingly.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCISavings(): void
    {
        $ci = Lookup::where('code', SendUpdateLogStatusEnum::CI)->first();

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'delays-unhappy-insurer',
            'code' => 'DWI',
            'text' => 'Delays/Unhappy with insurer',
        ], [
            'parent_id' => $ci->id,
            'description' => 'Requires cancellation due to the delays or issues related to communication, processing, or services provided by the selected insurer.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Savings,
            'key' => 'unhappy-our-service',
            'code' => 'UWOS',
            'text' => 'Unhappy with our service',
        ], [
            'parent_id' => $ci->id,
            'description' => 'Requires cancellation due to the delays or issues related to communication, processing, or services we provided.',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createRmCategories(): void
    {
        Lookup::firstOrCreate([
            'key' => LookupsEnum::RM_CATEGORY,
            'code' => 'core',
            'text' => 'Core',
        ], [
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'key' => LookupsEnum::RM_CATEGORY,
            'code' => 'rgta',
            'text' => 'RGTA',
        ], [
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create referral sources lookup data for all quote types
     */
    private function createReferralSources(): void
    {
        // Define the referral sources structure
        $referralSources = [
            [
                'text' => "Advisor's own referral",
                'code' => 'advisors-own-referral',
                'description' => "This lead is sourced from advisor's personal network (family or friend).",
                'children' => [],
            ],
            [
                'text' => 'Existing IM Customers',
                'code' => 'existing-im-customers',
                'description' => 'This is a cross-sell lead',
                'children' => [
                    [
                        'text' => 'IM Cross-sell',
                        'code' => 'im-cross-sell',
                        'description' => "This lead is a new business opportunity of IM's legacy customer.",
                    ],
                    [
                        'text' => 'PCP Cross-sell',
                        'code' => 'pcp-cross-sell',
                        'description' => 'This lead is a new business opportunity of a IM_PCP customer.',
                    ],
                ],
            ],
            [
                'text' => 'Customer Referrals',
                'code' => 'customer-referrals',
                'description' => 'This is a referral from IM Customer.',
                'children' => [
                    [
                        'text' => 'IM customer referral',
                        'code' => 'im-customer-referral',
                        'description' => "This lead is of a new customer referred by IM's legacy customer.",
                    ],
                    [
                        'text' => 'PCP customer referral',
                        'code' => 'pcp-customer-referral',
                        'description' => 'This lead is of a new customer referred by a PCP customer.',
                    ],
                ],
            ],
            [
                'text' => 'IM Internal Referrals',
                'code' => 'im-internal-referrals',
                'description' => 'This is a referral from IM department or colleague.',
                'children' => [],
            ],
            [
                'text' => 'Strategic Partners Referrals',
                'code' => 'strategic-partners-referrals',
                'description' => 'This is a new customer referred from IM partnered groups, clubs or campaigns.',
                'children' => [
                    [
                        'text' => 'CEO club',
                        'code' => 'ceo-club',
                        'description' => null,
                    ],
                    [
                        'text' => "Young Presidents' Organization (YPO)",
                        'code' => 'young-presidents-organization-ypo',
                        'description' => null,
                    ],
                    [
                        'text' => 'Other clubs or campaigns',
                        'code' => 'other-clubs-or-campaigns',
                        'description' => null,
                    ],
                ],
            ],
            [
                'text' => 'ECOM Lead Extension',
                'code' => 'ecom-lead-extension',
                'description' => 'This lead is an extension of an ECOM lead.',
                'children' => [],
            ],
        ];

        foreach ($referralSources as $parentSource) {
            // Create parent lookup
            $parent = Lookup::firstOrCreate([
                'key' => LookupsEnum::SUB_SOURCE,
                'code' => $parentSource['code'],
                'text' => $parentSource['text'],
            ], [
                'description' => $parentSource['description'],
                'parent_id' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create children if they exist
            if (! empty($parentSource['children'])) {
                foreach ($parentSource['children'] as $childSource) {
                    Lookup::firstOrCreate([
                        'key' => LookupsEnum::SUB_SOURCE_OPTION,
                        'code' => $childSource['code'],
                        'text' => $childSource['text'],
                    ], [
                        'description' => $childSource['description'],
                        'parent_id' => $parent->id,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
