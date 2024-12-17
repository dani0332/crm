<?php

namespace Database\Seeders;

use App\Enums\LookupsEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\BusinessInsuranceType;
use App\Models\Lookup;
use Illuminate\Database\Seeder;

class AddSendUpdatesCategoriesInLookups extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = $this->getGenericData();

        $allLOBs = $this->getAllLOBs();

        foreach ($data as $option) { // Endorsement, Cancellation from inception, Correction of policy.
            $parentOption = Lookup::firstOrCreate([
                'key' => $option['key'],
                'text' => $option['name'],
                'code' => LookupsEnum::SEND_UPDATE_CODE,
                'description' => $option['tooltip'],
                'parent_id' => null,
            ]);
            // info('Main Type: '.$option['name']);

            foreach ($option['types'] as $type) { // EF EN, CI CIR, CPU CPD.
                $typeCategory = Lookup::firstOrCreate([
                    'key' => $type['key'],
                    'text' => $type['name'],
                    'code' => $type['code'],
                    'parent_id' => $parentOption->id,
                ], [
                    'description' => $type['tooltip'],
                ]);
                // info("\tSub Type: ".$type['name']);

                foreach ($type['subTypes'] as $lob => $subTypes) { // all subtypes

                    if ($lob === 'Corpline' || $lob === 'GroupMedical' || $lob === 'MotorFleet') {
                        $item = $allLOBs->where('name', '=', 'Business')->first();
                    } else {
                        $item = $allLOBs->where('name', '=', $lob)->first();
                    }

                    if (! $item) {
                        continue;
                    }

                    $businessInsuranceTypeId = null;
                    if (in_array($lob, ['GroupMedical', 'MotorFleet'])) {
                        if ($lob === 'GroupMedical') {
                            $businessInsuranceType = quoteBusinessTypeCode::groupMedical;
                        } elseif ($lob === 'MotorFleet') {
                            $businessInsuranceType = quoteBusinessTypeCode::carFleet;
                        }

                        $businessInsuranceTypeId = BusinessInsuranceType::where('code', $businessInsuranceType)->first()->id;
                    }

                    foreach ($subTypes as $subType) {
                        $words = explode(' ', ucwords($subType['name']));
                        $code = '';
                        collect($words)->each(function ($word) use (&$code) {
                            $code .= substr($word, 0, 1);
                        });

                        $child = Lookup::firstOrCreate([
                            'quote_type_id' => $item['id'],
                            'business_insurance_type_id' => $businessInsuranceTypeId ?? null,
                            'key' => $subType['key'],
                            'text' => $subType['name'],
                            'code' => $subType['code'] ?? $code,
                            'parent_id' => $typeCategory->id,
                        ], [
                            'description' => $subType['tooltip'],
                        ]);
                    }
                }
            }
        }
    }

    private function getAllLOBs()
    {
        return collect(QuoteTypeId::getOptions())->map(function ($value, $key) {
            return [
                'id' => $key,
                'name' => $value,
            ];
        });
    }

    private function getGenericData()
    {
        return [
            [
                'name' => 'Endorsement',
                'key' => 'endorsement',
                'tooltip' => 'Modifying or amending an existing policy. This includes actions like Additional cover and requesting corrections for any details in the existing policy from the Insurer.',
                'types' => [
                    [
                        'name' => 'Endorsement financial',
                        'key' => 'endorsement-financial',
                        'code' => SendUpdateLogStatusEnum::EF,
                        'tooltip' => 'Modifications or revisions to an existing policy that result in financial implications. This encompasses actions like adding extra coverage, midterm addition or removal of members, and extending the policy duration. There is involvement of collection an additional amount or a refund of a certain amount in the policy.',
                        'subTypes' => $this->getEndorsementFinancialSubTypes(),
                    ],
                    [
                        'name' => 'Endorsement non financial',
                        'key' => 'endorsement-non-financial',
                        'code' => SendUpdateLogStatusEnum::EN,
                        'tooltip' => 'Modifications or alterations made to an existing policy without any associated financial effects. This includes actions such as name amendments, details to be updated and requests for certificates of insurance. Here, there is no involvement of collecting or refunding any amount.',
                        'subTypes' => $this->getEndorsementNonFinancialSubTypes(),
                    ],
                ],
            ],
            [
                'name' => 'Cancellation from inception',
                'key' => 'cancellation-from-inception',
                'tooltip' => 'With this option, policyholders can request the cancellation of their insurance policy from the inception date. This means the policy will be considered null and void as if it was never in effect.',
                'types' => [
                    [
                        'name' => 'Cancellation from inception',
                        'key' => 'cancellation-from-inception',
                        'code' => SendUpdateLogStatusEnum::CI,
                        'tooltip' => 'With this option, policyholders can request the cancellation of their insurance policy from the inception date. This means the policy will be considered null and void as if it was never in effect',
                        'subTypes' => $this->getCancellationFromInceptionSubTypes(),
                    ],
                    [
                        'name' => 'Cancellation from inception and reissuance',
                        'key' => 'cancellation-inception-reissuance',
                        'code' => SendUpdateLogStatusEnum::CIR,
                        'tooltip' => 'To cancel the insurance policy from the inception date and subsequently reissue a new policy as required. This is typically used if we started a policy with an incorrect inception date, there is a change in the insurer selected and an update to the covers included in the policy.',
                        'subTypes' => $this->getCancellationFromReissuanceSubTypes(),
                    ],
                ],
            ],
            [
                'name' => 'Correction of policy',
                'key' => 'correction-of-policy',
                'tooltip' => "To rectify errors or inaccuracies in insurance policy, ensuring that the policy details align with the customer intended coverage. It's a valuable feature for maintaining accurate and up-to-date policies",
                'types' => [
                    [
                        'name' => 'Correction of policy upload',
                        'key' => 'correction-of-policy-upload',
                        'code' => SendUpdateLogStatusEnum::CPU,
                        'tooltip' => 'This feature enables advisors to rectify any errors or inaccuracies in insurance policy documents. It ensures that the policy documentation is accurate and up to date. This could include scenarios where we sent the incorrect policy documents to the client, which belonged to another client.',
                        'subTypes' => [],
                    ],
                    [
                        'name' => 'Correction of policy details',
                        'key' => 'correction-of-policy-details',
                        'code' => SendUpdateLogStatusEnum::CPD,
                        'tooltip' => 'To correct errors or inaccuracies in the insurance policy documents, ensuring that the policy information is accurate and aligned with their coverage needs.',
                        'subTypes' => [],
                    ],
                ],
            ],
        ];
    }

    private function getEndorsementFinancialSubTypes()
    {
        return [
            'Car' => [
                [
                    'name' => 'Add optional cover',
                    'key' => 'add-optional-cover',
                    'code' => SendUpdateLogStatusEnum::AOCOV,
                    'tooltip' => "To include an additional coverage option such for a rental car, Oman cover, GCC cover, Personal accident benefit covers, roadside assistance, etc. to enhance protection as per the policyholder's specific needs",
                ],
                [
                    'name' => 'Change of Emirate',
                    'key' => 'change-emirates',
                    'tooltip' => 'Refers to the process of updating or modifying the Emirate of Registration, as per the policyholder.',
                ],
                [
                    'name' => 'Change in seating capacity',
                    'key' => 'change-seating-capacity',
                    'tooltip' => "Indicates that you can modify the seating capacity of the insured vehicle. This change is typically made to ensure that the policy accurately reflects the vehicle's specifications. Please update the seating capacity information accurately to maintain policy accuracy.",
                ],
                [
                    'name' => 'Policy period extension',
                    'key' => 'policy-period-extension',
                    'tooltip' => 'To extend the duration or terms of their existing or current insurance policy beyond the original expiration date, providing continuous coverage.',
                ],
                [
                    'name' => 'Midterm policy cancellation',
                    'key' => 'midterm-policy-cancellation',
                    'tooltip' => 'Select this if policyholders wish to terminate their insurance coverage before the policy ends. This is often chosen in situations like selling the car, exporting the car outside of the UAE or experiencing a total loss claim on their vehicle. Ensure to verify the reason with the policyholder and select accordingly.',
                ],
                [
                    'name' => 'Additional commission booking',
                    'key' => 'additional-commission-booking',
                    'code' => SendUpdateLogStatusEnum::ACB,
                    'tooltip' => '',
                ],
                [
                    'name' => 'Additional tax invoice booking',
                    'key' => 'additional-tax-invoice-booking',
                    'code' => SendUpdateLogStatusEnum::ATIB,
                    'tooltip' => '',
                ],

            ],
            'Bike' => [
                [
                    'name' => 'Add optional cover',
                    'key' => 'add-optional-cover',
                    'code' => 'BIAOCOV',
                    'tooltip' => "To include an additional coverage option such for Oman cover, Personal accident benefit covers, roadside assistance, etc. to enhance protection as per the policyholder's specific needs",
                ],
                [
                    'name' => 'Change of Emirate',
                    'key' => 'change-emirates',
                    'tooltip' => 'Refers to the process of updating or modifying the Emirate of Registration, as per the policyholder.',
                ],
                [
                    'name' => 'Change in seating capacity',
                    'key' => 'change-seating-capacity',
                    'tooltip' => "Indicates that you can modify the seating capacity of the insured vehicle. This change is typically made to ensure that the policy accurately reflects the vehicle's specifications. Please update the seating capacity information accurately to maintain policy accuracy",
                ],
                [
                    'name' => 'Policy period extension',
                    'key' => 'policy-period-extension',
                    'tooltip' => 'To extend the duration or terms of their existing or current insurance policy beyond the original expiration date, providing continuous coverage.',
                ],
                [
                    'name' => 'Midterm policy cancellation',
                    'key' => 'midterm-policy-cancellation',
                    'tooltip' => 'Select this if policyholders wish to terminate their insurance coverage before the policy ends. This is often chosen in situations like selling the bike, exporting it outside of the UAE or experiencing a total loss claim on their vehicle. Ensure to verify the reason with the policyholder and select accordingly.',
                ],
                [
                    'name' => 'Additional commission booking',
                    'key' => 'additional-commission-booking',
                    'code' => SendUpdateLogStatusEnum::ACB,
                    'tooltip' => '',
                ],
                [
                    'name' => 'Additional tax invoice booking',
                    'key' => 'additional-tax-invoice-booking',
                    'code' => SendUpdateLogStatusEnum::ATIB,
                    'tooltip' => '',
                ],

            ],
            'Health' => [
                [
                    'name' => 'Midterm addition of member',
                    'key' => 'midterm-addition-of-member',
                    'tooltip' => 'To add new member(s) to their health insurance policy during the policy term, ensuring comprehensive coverage for their needs. This may involve collection of an additional premium amount, please check with the insurer.',
                ],
                [
                    'name' => 'Midterm deletion of member',
                    'key' => 'midterm-deletion-of-member',
                    'tooltip' => "Removing member(s) from their health insurance policy before the policy's scheduled expiration date offers flexibility in managing their coverage. This may lead to a credit due to the policyholder.",
                ],
                [
                    'name' => 'Midterm declaration',
                    'key' => 'midterm-declaration',
                    'tooltip' => 'To add a declaration of a pre-existing or new medical condition during the policy term. This can lead to collection of an additional premium amount from the policyholder, please check with the Insurer.',
                ],
                [
                    'name' => 'Marital status change',
                    'key' => 'marital-status-change',
                    'tooltip' => 'To update their marital status during their health insurance policy term, ensuring coverage alignment with their current life circumstances. This will involve an additional premium to be collected from the policyholder, please check with the insurer.',
                ],
                [
                    'name' => 'Midterm policy cancellation',
                    'key' => 'midterm-policy-cancellation',
                    'tooltip' => "This option allows policyholders to terminate their insurance before its scheduled end date. Common reasons include leaving the country, obtaining a new insurance policy elsewhere (e.g., a new employer), or the unfortunate event of the policyholder's passing. Always confirm the reason before processing.",
                ],
                [
                    'name' => 'Additional commission booking',
                    'key' => 'additional-commission-booking',
                    'code' => SendUpdateLogStatusEnum::ACB,
                    'tooltip' => '',
                ],
                [
                    'name' => 'Additional tax invoice booking',
                    'key' => 'additional-tax-invoice-booking',
                    'code' => SendUpdateLogStatusEnum::ATIB,
                    'tooltip' => '',
                ],

            ],
            'Travel' => [
                [
                    'name' => 'Change in travel duration',
                    'key' => 'change-travel-duration',
                    'tooltip' => "This endorsement allows policyholders to modify the number of travel days their insurance covers. It's an essential feature for travelers whose plans may change after purchasing their policy. This may involve an additional premium; please check with the insurer accordingly.",
                ],
                [
                    'name' => 'Change travel dates',
                    'key' => 'change-travel-dates',
                    'tooltip' => 'This is to modify the travel dates originally outlined in their insurance policy. It offers flexibility to accommodate changes in travel plans, ensuring coverage aligns with the new itinerary. Additional premium amount may be required from the policyholder to incorporate these changes, please check with the insurer on the same.',
                ],
                [
                    'name' => 'Correction of age bond',
                    'key' => 'correction-age-bond',
                    'tooltip' => 'This endorsement allows policyholders correct the age bond to reflect the correct age information. This may involve an additional premium; please check with the insurer accordingly.',
                ],
                [
                    'name' => 'Add member',
                    'key' => 'add-member',
                    'tooltip' => 'This endorsement allows policyholders to include an additional insured member in their travel insurance policy, which may require an extra premium',
                ],
                [
                    'name' => 'Delete member',
                    'key' => 'delete-member',
                    'tooltip' => 'This endorsement allows policyholders to remove an insured member from their travel insurance policy if needed. This may lead to a credit due to the policyholder.',
                ],
                [
                    'name' => 'Additional commission booking',
                    'key' => 'additional-commission-booking',
                    'code' => SendUpdateLogStatusEnum::ACB,
                    'tooltip' => '',
                ],
                [
                    'name' => 'Additional tax invoice booking',
                    'key' => 'additional-tax-invoice-booking',
                    'code' => SendUpdateLogStatusEnum::ATIB,
                    'tooltip' => '',
                ],

            ],
            'Life' => [
                [
                    'name' => 'Midterm policy cancellation',
                    'key' => 'midterm-policy-cancellation',
                    'tooltip' => 'This option allows policyholders to terminate their insurance before its scheduled end date. Common reasons include leaving the country, obtaining a new insurance policy elsewhere (e.g., a new employer), or the premium payments has lapsed from the policyholder. Always confirm the reason before processing.',
                ],
                [
                    'name' => 'Reinstatement',
                    'key' => 'reinstatement',
                    'tooltip' => 'Reinstatement refers to the act of bringing a lapsed or suspended life insurance policy back into active status. Ensure all conditions are met and necessary documentation is provided before proceeding with the reinstatement process. Be aware that additional payments may be required to fully reinstate the policy.',
                ],
                [
                    'name' => 'Additional commission booking',
                    'key' => 'additional-commission-booking',
                    'code' => SendUpdateLogStatusEnum::ACB,
                    'tooltip' => '',
                ],
                [
                    'name' => 'Additional tax invoice booking',
                    'key' => 'additional-tax-invoice-booking',
                    'code' => SendUpdateLogStatusEnum::ATIB,
                    'tooltip' => '',
                ],

            ],
            'Home' => [
                [
                    'name' => 'Additional Cover',
                    'key' => 'additional-cover',
                    'tooltip' => "Opt for this when you wish to add extra protection or coverages to the existing home insurance policy, such as new items or increased risk factors not originally included.\nNote: Adding additional cover may result in a premium increase, which will need to be collected from the policyholder.",
                ],
                [
                    'name' => 'Increase the sum insured',
                    'key' => 'increase-sum-insured',
                    'tooltip' => "Select this when you want to enhance the total amount for which your home is insured, possibly due to home improvements or acquisition of valuable items.\nBe aware: Increasing the sum insured will likely incur additional premiums that must be collected from the policyholder.",
                ],
                [
                    'name' => 'Decrease the sum insured',
                    'key' => 'decrease-sum-insured',
                    'tooltip' => 'Choose this option if you wish to reduce the overall amount for which your home is covered. This could be in scenarios where certain insured items are no longer in possession or if the property value has depreciated.',
                ],
                [
                    'name' => 'Midterm policy cancellation',
                    'key' => 'midterm-policy-cancellation',
                    'tooltip' => 'This selection is for instances when a policyholder opts to terminate their home insurance before its scheduled expiration. Reasons might include selling the property, transitioning to a different insurer, or other personal circumstances. Ensure all conditions are met for a midterm cancellation.',
                ],
                [
                    'name' => 'Additional commission booking',
                    'key' => 'additional-commission-booking',
                    'code' => SendUpdateLogStatusEnum::ACB,
                    'tooltip' => '',
                ],
                [
                    'name' => 'Additional tax invoice booking',
                    'key' => 'additional-tax-invoice-booking',
                    'code' => SendUpdateLogStatusEnum::ATIB,
                    'tooltip' => '',
                ],

            ],
            'Pet' => [
                [
                    'name' => 'Additional Cover',
                    'key' => 'additional-cover',
                    'tooltip' => "Choose this option to incorporate extra protection or benefits to the existing pet insurance. This could be due to new health concerns, additional pets, or other evolving needs of the policyholder's pet.\nKeep in mind: Opting for additional cover might lead to an increase in premium, to be collected from the policyholder.",
                ],
                [
                    'name' => 'Midterm policy cancellation',
                    'key' => 'midterm-policy-cancellation',
                    'tooltip' => "Select this if the policyholder wishes to terminate the pet insurance before its intended expiration date. This may be due to various reasons such as the pet's unfortunate passing, rehoming, or a change in the owner's circumstances. Remember, depending on the terms, some fees or penalties might apply for midterm cancellations.",
                ],
                [
                    'name' => 'Additional commission booking',
                    'key' => 'additional-commission-booking',
                    'code' => SendUpdateLogStatusEnum::ACB,
                    'tooltip' => '',
                ],
                [
                    'name' => 'Additional tax invoice booking',
                    'key' => 'additional-tax-invoice-booking',
                    'code' => SendUpdateLogStatusEnum::ATIB,
                    'tooltip' => '',
                ],

            ],
            'Cycle' => [
                [
                    'name' => 'Additional Cover',
                    'key' => 'additional-cover',
                    'tooltip' => "Opt for this when the policyholder seeks to bolster their bicycle protection. This could be to cover additional accessories, specific events or races, or due to upgrades made to the cycle.\nRemember: Enhancing or adding covers may result in a higher premium amount, to be collected from the policyholder.",
                ],
                [
                    'name' => 'Midterm policy cancellation',
                    'key' => 'midterm-policy-cancellation',
                    'tooltip' => 'Choose this option if the policyholder wants to terminate the cycle insurance before its scheduled end date. This can arise from selling the bicycle, switching to a different insurer, or other personal circumstances. Certain terms may apply, including potential fees or penalties for midterm cancellations.',
                ],
                [
                    'name' => 'Additional commission booking',
                    'key' => 'additional-commission-booking',
                    'code' => SendUpdateLogStatusEnum::ACB,
                    'tooltip' => '',
                ],
                [
                    'name' => 'Additional tax invoice booking',
                    'key' => 'additional-tax-invoice-booking',
                    'code' => SendUpdateLogStatusEnum::ATIB,
                    'tooltip' => '',
                ],

            ],
            'Yacht' => [
                [
                    'name' => 'Additional Cover',
                    'key' => 'additional-cover',
                    'tooltip' => 'To add additional cover to enhance the insurance protection and receive additional benefits as per the selected coverage',
                ],
                [
                    'name' => 'Policy period extension',
                    'key' => 'policy-period-extension',
                    'tooltip' => 'To extend the duration or terms of their existing or current insurance policy beyond the original expiration date, providing continuous coverage.',
                ],
                [
                    'name' => 'Midterm policy cancellation',
                    'key' => 'midterm-policy-cancellation',
                    'tooltip' => "To have the flexibility to cancel their insurance policy before the scheduled expiration date. It's a valuable option for those who need to make changes to their coverage during the policy term.",
                ],
                [
                    'name' => 'Additional commission booking',
                    'key' => 'additional-commission-booking',
                    'code' => SendUpdateLogStatusEnum::ACB,
                    'tooltip' => '',
                ],
                [
                    'name' => 'Additional tax invoice booking',
                    'key' => 'additional-tax-invoice-booking',
                    'code' => SendUpdateLogStatusEnum::ATIB,
                    'tooltip' => '',
                ],

            ],
            'MotorFleet' => [
                [
                    'name' => 'Additional vehicle',
                    'key' => 'additional-vehicle',
                    'tooltip' => 'To add additional vehicle to extend the coverage and ensure multiple vehicles are protected under the same insurance plan.',
                ],
                [
                    'name' => 'Deletion of vehicle',
                    'key' => 'deletion-vehicle',
                    'tooltip' => 'To delete the vehicle from the policy coverage if it is no longer needed or if no longer owned by the company.',
                ],
                [
                    'name' => 'Add optional cover',
                    'key' => 'add-optional-cover',
                    'code' => 'MFAOCOV',
                    'tooltip' => "To include an additional coverage option such for a rental car, Oman cover, GCC cover, Personal accident benefit covers, roadside assistance, etc. to enhance protection as per the policyholder's specific needs.",
                ],
                [
                    'name' => 'Change of Emirate',
                    'key' => 'change-emirates',
                    'tooltip' => 'Refers to the process of updating or modifying the Emirate of Registration, as per the policyholder.',
                ],
                [
                    'name' => 'Change in seating capacity',
                    'key' => 'change-seating-capacity',
                    'tooltip' => "Indicates that you can modify the seating capacity of the insured vehicle. This change is typically made to ensure that the policy accurately reflects the vehicle's specifications. Please update the seating capacity information accurately to maintain policy accuracy.",
                ],
                [
                    'name' => 'Policy period extension',
                    'key' => 'policy-period-extension',
                    'tooltip' => 'To extend the duration or terms of their existing or current insurance policy beyond the original expiration date, providing continuous coverage.',
                ],
                [
                    'name' => 'Midterm policy cancellation',
                    'key' => 'midterm-policy-cancellation',
                    'tooltip' => 'Select this if policyholders wish to terminate their insurance coverage before the policy ends. This is often chosen in situations like selling the car, exporting the car outside of the UAE or experiencing a total loss claim on their vehicle. Ensure to verify the reason with the policyholder and select accordingly.',
                ],
                [
                    'name' => 'Additional commission booking',
                    'key' => 'additional-commission-booking',
                    'code' => SendUpdateLogStatusEnum::ACB,
                    'tooltip' => '',
                ],
                [
                    'name' => 'Additional tax invoice booking',
                    'key' => 'additional-tax-invoice-booking',
                    'code' => SendUpdateLogStatusEnum::ATIB,
                    'tooltip' => '',
                ],

            ],
            'GroupMedical' => [
                [
                    'name' => 'Midterm addition of member',
                    'key' => 'midterm-addition-of-member',
                    'tooltip' => 'To add new member(s) to their health insurance policy during the policy term, ensuring comprehensive coverage for their needs. This may involve collection of an additional premium amount, please check with the insurer.',
                ],
                [
                    'name' => 'Midterm deletion of member',
                    'key' => 'midterm-deletion-of-member',
                    'tooltip' => "Removing member(s) from their health insurance policy before the policy's scheduled expiration date offers flexibility in managing their coverage. This may lead to a credit due to the policyholder.",
                ],
                [
                    'name' => 'Midterm declaration',
                    'key' => 'midterm-declaration',
                    'tooltip' => 'To add a declaration of a pre-existing or new medical condition during the policy term. This can lead to collection of an additional premium amount from the policyholder, please check with the Insurer.',
                ],
                [
                    'name' => 'Marital status change',
                    'key' => 'marital-status-change',
                    'tooltip' => 'To update their marital status during their health insurance policy term, ensuring coverage alignment with their current life circumstances. This will involve an additional premium to be collected from the policyholder, please check with the insurer.',
                ],
                [
                    'name' => 'Midterm policy cancellation',
                    'key' => 'midterm-policy-cancellation',
                    'tooltip' => "This option allows policyholders to terminate their insurance before its scheduled end date. Common reasons include leaving the country, obtaining a new insurance policy elsewhere (e.g., a new employer), or the unfortunate event of the policyholder's passing. Always confirm the reason before processing.",
                ],
                [
                    'name' => 'Plan upgrade',
                    'key' => 'plan-upgrade',
                    'tooltip' => "To upgrade member's coverage by changing their category, a copy of promotion letter is necessary. This is accessible only for group medical policies that have two or more categories. This will involve an additional premium to be collected from the policyholder, please check with the insurer.",
                ],
                [
                    'name' => 'Sub-group creation',
                    'key' => 'sub-group-creation',
                    'tooltip' => 'To add new member(s) into their existing health insurance policy, particularly those from a sister company, that requires to establish a sub-group within the group medical insurance policy.. This will involve an additional premium to be collected from the policyholder, please check with the insurer.',
                ],
                [
                    'name' => 'Additional commission booking',
                    'key' => 'additional-commission-booking',
                    'code' => SendUpdateLogStatusEnum::ACB,
                    'tooltip' => '',
                ],
                [
                    'name' => 'Additional tax invoice booking',
                    'key' => 'additional-tax-invoice-booking',
                    'code' => SendUpdateLogStatusEnum::ATIB,
                    'tooltip' => '',
                ],

            ],
            'Corpline' => [
                [
                    'name' => 'Addition of location of practice for medical professionals',
                    'key' => 'addition-location-practice',
                    'tooltip' => 'This allows you to add new practice locations for medical professionals. Ensure accurate details are provided to maintain comprehensive coverage. This may involve collection of an additional premium amount, please check with the insurer.',
                ],
                [
                    'name' => 'Additional Cover',
                    'key' => 'additional-cover',
                    'tooltip' => 'To add additional cover to enhance the insurance protection and receive additional benefits as per the selected coverage This may involve collection of an additional premium amount, please check with the insurer.',
                ],
                [
                    'name' => 'Additional location',
                    'key' => 'additional-location',
                    'tooltip' => 'This is to include an additional location or property under their coverage. It offers the flexibility to extend protection to new assets or places beyond the initial policy terms. This may involve collection of an additional premium amount, please check with the insurer.',
                ],
                [
                    'name' => 'Employee addition',
                    'key' => 'employee-addition',
                    'tooltip' => "This is to add additional employees to their workers' compensation coverage. It ensures that all eligible workers are included in the policy, providing comprehensive protection for the workforce. This may involve collection of an additional premium amount, please check with the insurer.",
                ],
                [
                    'name' => 'Employee deletion',
                    'key' => 'employee-deletion',
                    'tooltip' => "This is to delete employees to their workers' compensation coverage. It ensures that all eligible workers are included in the policy, providing comprehensive protection for the workforce. This may lead to a credit due to the policyholder.",
                ],
                [
                    'name' => 'Extension for maintenance period',
                    'key' => 'extension-maintenance-period',
                    'tooltip' => 'Extend the maintenance period as necessary. This allows to prolong the period during which maintenance and support services are provided. This may involve collection of an additional premium amount, please check with the insurer.',
                ],
                [
                    'name' => 'Increase / Change of limit of indemnity and limit of liability required',
                    'key' => 'increase-change-estimate-annual-fees',
                    'code' => SendUpdateLogStatusEnum::I_CLILLR,
                    'tooltip' => "Modify or enhance the policy's limit of indemnity and limit of liability as required. Depending on the changes made, a credit or an additional payment maybe due to the policyholder.",
                ],
                [
                    'name' => 'Increase in estimated annual fees / turnover',
                    'key' => 'increase-estimate-annual-fees',
                    'code' => SendUpdateLogStatusEnum::IEAF_T,
                    'tooltip' => 'Update on the estimated annual fees or turnover to reflect changes in your business. Accurate information ensures adequate coverage. This may involve collection of an additional premium amount, please check with the insurer.',
                ],
                [
                    'name' => 'Increase in sum Insured',
                    'key' => 'increase-sum-insured',
                    'tooltip' => 'Increase the insured amount for your policy here. Review and adjust the sum insured as needed to match your evolving needs. This may involve collection of an additional premium amount, please check with the insurer.',
                ],
                [
                    'name' => 'Midterm Policy Cancellation',
                    'key' => 'midterm-policy-cancellation',
                    'tooltip' => "To have the flexibility to cancel their insurance policy before the scheduled expiration date. It's a valuable option for those who need to make changes to their coverage during the policy term.",
                ],
                [
                    'name' => 'Policy period extension',
                    'key' => 'policy-period-extension',
                    'tooltip' => 'To extend the duration or terms of their existing or current insurance policy beyond the original expiration date, providing continuous coverage. This may involve collection of an additional premium amount, please check with the insurer.',
                ],
                [
                    'name' => 'Additional commission booking',
                    'key' => 'additional-commission-booking',
                    'code' => SendUpdateLogStatusEnum::ACB,
                    'tooltip' => '',
                ],
                [
                    'name' => 'Additional tax invoice booking',
                    'key' => 'additional-tax-invoice-booking',
                    'code' => SendUpdateLogStatusEnum::ATIB,
                    'tooltip' => '',
                ],

            ],
            // 'Jetski' => [
            //     //
            // ]
        ];
    }

    private function getEndorsementNonFinancialSubTypes()
    {
        return [
            'Car' => [
                [
                    'name' => 'Change in seating capacity (with no financial impact)',
                    'key' => 'change-seating-capacity-nf',
                    'code' => SendUpdateLogStatusEnum::CISC_NFI,
                    'tooltip' => "Indicates that you can modify the seating capacity of the insured vehicle without any financial impact. This change is typically made to ensure that the policy accurately reflects the vehicle's specifications. Please update the seating capacity information accurately to maintain policy accuracy.",
                ],
                [
                    'name' => 'Change of Emirates (with no financial impact)',
                    'key' => 'change-emirates-nf',
                    'code' => SendUpdateLogStatusEnum::COE_NFI,
                    'tooltip' => 'Refers to the process of updating or modifying the geographical location or emirates associated with an insurance policy with no financial impact.',
                ],
                [
                    'name' => 'Correction and amendments',
                    'key' => 'correction-amendments',
                    'tooltip' => 'To modify particular details or correct any inaccuracies within the policy records, these are the fields that can be updated without any financial implications. If there is a requirement to change customer name, policy number, start date, or end date in IMCRM, kindly reach out to the production team to rectify these policy details in the IMCRM system after sending the update to the customer.',
                ],
            ],
            'Bike' => [
                [
                    'name' => 'Change in seating capacity (with no financial impact)',
                    'key' => 'change-seating-capacity-nf',
                    'code' => SendUpdateLogStatusEnum::CISC_NFI,
                    'tooltip' => "Indicates that you can modify the seating capacity of the insured vehicle without any financial impact. This change is typically made to ensure that the policy accurately reflects the vehicle's specifications. Please update the seating capacity information accurately to maintain policy accuracy",
                ],
                [
                    'name' => 'Change of Emirates (with no financial impact)',
                    'key' => 'change-emirates-nf',
                    'code' => SendUpdateLogStatusEnum::COE_NFI,
                    'tooltip' => 'Refers to the process of updating or modifying the geographical location or emirates associated with an insurance policy with no financial impact',
                ],
                [
                    'name' => 'Correction and amendments',
                    'key' => 'correction-amendments',
                    'tooltip' => 'To modify particular details or correct any inaccuracies within the policy records, these are the fields that can be updated without any financial implications. If there is a requirement to change customer name, policy number, start date, or end date in IMCRM, kindly reach out to the production team to rectify these policy details in the IMCRM system after sending the update to the customer.',
                ],
            ],
            'Health' => [
                [
                    'name' => 'Correction and amendments',
                    'key' => 'correction-amendments',
                    'tooltip' => 'To modify particular details or correct any inaccuracies within the policy records supplied by the insurer, these are the fields that can be updated without any financial implications. If there is a requirement to change customer name, policy number, start date, or end date in IMCRM, kindly reach out to the finance team to rectify these policy details in the IMCRM system after sending the update to the customer.',
                ],
                [
                    'name' => 'Emirates ID update',
                    'key' => 'emirates-id-update',
                    'tooltip' => "To update member's Emirates ID and link it to their Health Insurance.",
                ],
                [
                    'name' => 'Marital status change (with no financial impact)',
                    'key' => 'marital-status-change-nf',
                    'code' => SendUpdateLogStatusEnum::MSC_NFI,
                    'tooltip' => "To update their marital status within the policy records. This change does not affect the policy's financial aspects.",
                ],
                [
                    'name' => 'Quote request',
                    'key' => 'quote-request',
                    'tooltip' => 'To request a quotation or estimate for insurance coverage and associated costs.',
                ],
                [
                    'name' => 'Request for active member list',
                    'key' => 'request-active-member-list',
                    'tooltip' => 'To request details of all currently active members under the policy',
                ],
                [
                    'name' => 'Request for certificate of continuity',
                    'key' => 'request-certificate-continuity',
                    'tooltip' => 'To obtain a document that verifies the continuous coverage of your insurance policy',
                ],
                [
                    'name' => 'Request for certificate of insurance',
                    'key' => 'request-certificate-insurance',
                    'tooltip' => "To obtain a document that provides proof of member's insurance coverage.",
                ],
                [
                    'name' => 'Request for ecard copy',
                    'key' => 'request-ecard-copy',
                    'tooltip' => "To obtain a digital copy of member's health insurance card.",
                ],
                [
                    'name' => 'Waive off waiting period applied',
                    'key' => 'waive-off-waiting-period',
                    'tooltip' => "To request from the Insurer to have the waiting period cancelled, allowing the member's immediate access to their insurance benefits.",
                ],
            ],
            'Travel' => [
                [
                    'name' => 'Change travel dates (with no financial impact)',
                    'key' => 'change-travel-dates-nf',
                    'code' => SendUpdateLogStatusEnum::CTD_NFI,
                    'tooltip' => 'This enables policyholders to make adjustments to the originally specified travel dates in their insurance policy. It offers flexibility for travelers in case their plans change.',
                ],
                [
                    'name' => 'Correction and amendments',
                    'key' => 'correction-amendments',
                    'tooltip' => 'To modify particular details or correct any inaccuracies within the policy records supplied by the insurer, these are the fields that can be updated without any financial implications. If there is a requirement to change customer name, policy number, start date, or end date in IMCRM, kindly reach out to the finance team to rectify these policy details in the IMCRM system after sending the update to the customer.',
                ],
            ],
            'Life' => [
                [
                    'name' => 'Correction and amendments',
                    'key' => 'correction-amendments',
                    'tooltip' => 'To modify particular details or correct any inaccuracies within the policy records supplied by the insurer, these are the fields that can be updated without any financial implications. If there is a requirement to change customer name, policy number, start date, or end date in IMCRM, kindly reach out to the finance team to rectify these policy details in the IMCRM system after sending the update to the customer.',
                ],
            ],
            'Home' => [
                [
                    'name' => 'Change of address',
                    'key' => 'change-address',
                    'tooltip' => 'Use this feature to request a change of address within the policy records',
                ],
                [
                    'name' => 'Correction and amendments',
                    'key' => 'correction-amendments',
                    'tooltip' => 'To modify particular details or correct any inaccuracies within the policy records supplied by the insurer, these are the fields that can be updated without any financial implications. If there is a requirement to change customer name, policy number, start date, or end date in IMCRM, kindly reach out to the finance team to rectify these policy details in the IMCRM system after sending the update to the customer.',
                ],
            ],
            'Pet' => [
                [
                    'name' => 'Correction and amendments',
                    'key' => 'correction-amendments',
                    'tooltip' => 'To make changes to specific information or rectify any inaccuracies in the policy records provided by the insurer. These are the details to be changed with no financial impact',
                ],
            ],
            'Cycle' => [
                [
                    'name' => 'Correction and amendments',
                    'key' => 'correction-amendments',
                    'tooltip' => 'To modify particular details or correct any inaccuracies within the policy records supplied by the insurer, these are the fields that can be updated without any financial implications. If there is a requirement to change customer name, policy number, start date, or end date in IMCRM, kindly reach out to the finance team to rectify these policy details in the IMCRM system after sending the update to the customer.',
                ],
            ],
            'Yacht' => [
                [
                    'name' => 'Correction and amendments',
                    'key' => 'correction-amendments',
                    'tooltip' => 'To modify particular details or correct any inaccuracies within the policy records supplied by the insurer, these are the fields that can be updated without any financial implications. If there is a requirement to change customer name, policy number, start date, or end date in IMCRM, kindly reach out to the finance team to rectify these policy details in the IMCRM system after sending the update to the customer.',
                ],
            ],
            'MotorFleet' => [
                [
                    'name' => 'Change in seating capacity (with no financial impact)',
                    'key' => 'change-seating-capacity-nf',
                    'code' => SendUpdateLogStatusEnum::CISC_NFI,
                    'tooltip' => "Indicates that you can modify the seating capacity of the insured vehicle without any financial impact. This change is typically made to ensure that the policy accurately reflects the vehicle's specifications. Please update the seating capacity information accurately to maintain policy accuracy",
                ],
                [
                    'name' => 'Change of Emirates (with no financial impact)',
                    'key' => 'change-emirates-nf',
                    'code' => SendUpdateLogStatusEnum::COE_NFI,
                    'tooltip' => 'Refers to the process of updating or modifying the geographical location or emirates associated with an insurance policy with no financial impact',
                ],
                [
                    'name' => 'Correction and amendments',
                    'key' => 'correction-amendments',
                    'tooltip' => 'To modify particular details or correct any inaccuracies within the policy records supplied by the insurer, these are the fields that can be updated without any financial implications. If there is a requirement to change customer name, policy number, start date, or end date in IMCRM, kindly reach out to the non retail accounts team to rectify these policy details in the IMCRM system after sending the update to the customer.',
                ],
            ],
            'GroupMedical' => [
                [
                    'name' => 'Correction and amendments',
                    'key' => 'correction-amendments',
                    'tooltip' => 'To modify particular details or correct any inaccuracies within the policy records supplied by the insurer, these are the fields that can be updated without any financial implications. If there is a requirement to change customer name, policy number, start date, or end date in IMCRM, kindly reach out to the non retail accounts team to rectify these policy details in the IMCRM system after sending the update to the customer.',
                ],
                [
                    'name' => 'Emirates ID update',
                    'key' => 'emirates-id-update',
                    'tooltip' => "To update member's Emirates ID and link it to their Health Insurance",
                ],
                [
                    'name' => 'Marital status change (with no financial impact)',
                    'key' => 'marital-status-change-nf',
                    'code' => SendUpdateLogStatusEnum::MSC_NFI,
                    'tooltip' => "To update their marital status within the policy records. This change does not affect the policy's financial aspects.",
                ],
                [
                    'name' => 'Quote request',
                    'key' => 'quote-request',
                    'tooltip' => 'To request a quotation or estimate for insurance coverage and associated costs.',
                ],
                [
                    'name' => 'Request for active member list',
                    'key' => 'request-active-member-list',
                    'tooltip' => 'To request details of all currently active members under the policy.',
                ],
                [
                    'name' => 'Request for certificate of continuity',
                    'key' => 'request-certificate-continuity',
                    'tooltip' => 'To obtain a document that verifies the continuous coverage of your insurance policy.',
                ],
                [
                    'name' => 'Request for certificate of insurance',
                    'key' => 'request-certificate-insurance',
                    'tooltip' => "To obtain a document that provides proof of member's insurance coverage",
                ],
                [
                    'name' => 'Request for ecard copy',
                    'key' => 'request-ecard-copy',
                    'tooltip' => "To obtain a digital copy of member's health insurance card",
                ],
                [
                    'name' => 'Request for statement of account (SOA)',
                    'key' => 'request-statement-account',
                    'code' => SendUpdateLogStatusEnum::RF_SOAC,
                    'tooltip' => 'To request a Statement of Account (SOA) for customer policy',
                ],
                [
                    'name' => 'Request for tax invoice',
                    'key' => 'request-tax-invoice',
                    'tooltip' => "To request for a tax invoice related to customer's policy",
                ],
                [
                    'name' => 'Request for travel certificate',
                    'key' => 'request-travel-certificate',
                    'tooltip' => 'To request for a travel certificate associated withh the policy',
                ],
                [
                    'name' => 'Waive off waiting period applied',
                    'key' => 'waive-off-waiting-period',
                    'tooltip' => "To request from the Insurer to have the waiting period cancelled, allowing the member's immediate access to their insurance benefits",
                ],
            ],
            'Corpline' => [
                [
                    'name' => 'Add additional insured',
                    'key' => 'add-additional-insured',
                    'tooltip' => 'Include additional insured parties to the policy. This extends coverage to other individuals or entities as specified, broadening the protection provided by the policy.',
                ],
                [
                    'name' => 'Addition of clauses',
                    'key' => 'addition-clauses',
                    'tooltip' => 'Add new clauses to your policy as needed. This allows you to specify additional terms or conditions that are relevant to your coverage.',
                ],
                [
                    'name' => 'Change of address',
                    'key' => 'change-address',
                    'tooltip' => 'Update customer address information within their policy.',
                ],
                [
                    'name' => 'Correction and amendments',
                    'key' => 'correction-amendments',
                    'tooltip' => 'To modify particular details or correct any inaccuracies within the policy records supplied by the insurer, these are the fields that can be updated without any financial implications. If there is a requirement to change customer name, policy number, start date, or end date in IMCRM, kindly reach out to the non retail accounts team to rectify these policy details in the IMCRM system after sending the update to the customer.',
                ],
            ],
            // 'Jetski' => [
            //     //
            // ]
        ];
    }

    private function getCancellationFromInceptionSubTypes()
    {
        $data = [
            [
                'name' => 'Delays/Unhappy with insurer',
                'key' => 'delays-unhappy-insurer',
                'code' => 'DWI',
                'tooltip' => 'Requires cancellation due to the delays or issues related to communication, processing, or services provided by the selected insurer.',
            ],
            [
                'name' => 'Unhappy with our service',
                'key' => 'unhappy-our-service',
                'tooltip' => 'Requires cancellation due to the delays or issues related to communication, processing, or services we provided.',
            ],
        ];

        $allLOBs = $this->getAllLOBs();

        $subTypes = [];

        foreach ($allLOBs as $lob) {
            $subTypes[$lob['name']] = $data;
        }

        return $subTypes;
    }

    private function getCancellationFromReissuanceSubTypes()
    {
        $data = [
            [
                'name' => 'Change in inception date',
                'key' => 'change-inception-date',
                'tooltip' => "Requires cancellation and reissuance of the policy due to a change in the policy's start date.\nExample: Policy is issued with the start date as of today. Client has gotten back to us to request a change in the start date to a later date (future date) because of any reason, like, they have an existing policy until then.",
            ],
            [
                'name' => 'Change in expiry date / Extension of policy',
                'key' => 'change-expiry-extension',
                'code' => SendUpdateLogStatusEnum::CIED_EOP,
                'tooltip' => "Requires cancellation and re-issuance due to a policy's expiry date change.\nExample: Travel date extension of a trip, which has the same start date however, extension is made to the end date of the trip.",
            ],
            [
                'name' => 'Change in insurer',
                'key' => 'change-insurer',
                'tooltip' => "Requires cancellation and re-issuance due to a change of provider.\nExample: Client still needs to receive the benefits of the chosen insurer and hence wants to change their Insurer due to a delay. This change may involve a debit amount due or credit to the client.",
            ],
            [
                'name' => 'Change in cover',
                'key' => 'change-cover',
                'tooltip' => "Requires cancellation and re-issuance due to a change of cover.\nExample: Policy is not yet started, and the client now wants to add a cover, this would involve a cancellation of the current policy and re-issuance of the new policy with the cover(s) added accordingly.",
            ],
        ];

        $allLOBs = $this->getAllLOBs();

        $subTypes = [];

        foreach ($allLOBs as $lob) {
            $subTypes[$lob['name']] = $data;
        }

        return $subTypes;
    }
}
