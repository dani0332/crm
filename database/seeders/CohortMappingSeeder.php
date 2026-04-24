<?php

namespace Database\Seeders;

use App\Models\CohortMapping;
use Illuminate\Database\Seeder;

class CohortMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            // EMPLOYMENT - Yes
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'SELF', 'is_policyholder_covered' => 'Yes', 'cohort' => 'EMP'],
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'SPOUSE', 'is_policyholder_covered' => 'Yes', 'cohort' => 'DEP'],
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'CHILD_STANDARD', 'is_policyholder_covered' => 'Yes', 'cohort' => 'DEP'],
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'CHILD_EXTENDED_BY_POSITION', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'CHILD_ADULT_18PLUS', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'PARENT', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'SIBLING', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'DOMESTIC_WORKER', 'is_policyholder_covered' => 'Yes', 'cohort' => 'DWR'],

            // INVESTOR_PARTNER - Yes
            ['visa_type' => 'INVESTOR_PARTNER', 'member_classification' => 'SELF', 'is_policyholder_covered' => 'Yes', 'cohort' => 'INV'],
            ['visa_type' => 'INVESTOR_PARTNER', 'member_classification' => 'SPOUSE', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'INVESTOR_PARTNER', 'member_classification' => 'CHILD_STANDARD', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'INVESTOR_PARTNER', 'member_classification' => 'CHILD_EXTENDED_BY_POSITION', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'INVESTOR_PARTNER', 'member_classification' => 'CHILD_ADULT_18PLUS', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'INVESTOR_PARTNER', 'member_classification' => 'PARENT', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'INVESTOR_PARTNER', 'member_classification' => 'SIBLING', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],

            // GOLDEN_VISA - Yes
            ['visa_type' => 'GOLDEN_VISA', 'member_classification' => 'SELF', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'GOLDEN_VISA', 'member_classification' => 'SPOUSE', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'GOLDEN_VISA', 'member_classification' => 'CHILD_STANDARD', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'GOLDEN_VISA', 'member_classification' => 'CHILD_EXTENDED_BY_POSITION', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'GOLDEN_VISA', 'member_classification' => 'CHILD_ADULT_18PLUS', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'GOLDEN_VISA', 'member_classification' => 'PARENT', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'GOLDEN_VISA', 'member_classification' => 'SIBLING', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],

            // SELF_EMPLOYED_FREELANCER - Yes
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'SELF', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'SPOUSE', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'CHILD_STANDARD', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'CHILD_EXTENDED_BY_POSITION', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'CHILD_ADULT_18PLUS', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'PARENT', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'SIBLING', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'DOMESTIC_WORKER', 'is_policyholder_covered' => 'Yes', 'cohort' => 'DWR'],

            // DOMESTIC_WORKER - Yes
            ['visa_type' => 'DOMESTIC_WORKER', 'member_classification' => 'DOMESTIC_WORKER', 'is_policyholder_covered' => 'Yes', 'cohort' => 'DWR'],

            // EMPLOYMENT - No
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'SELF', 'is_policyholder_covered' => 'No', 'cohort' => 'EMP'],
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'SPOUSE', 'is_policyholder_covered' => 'No', 'cohort' => 'DEP'],
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'CHILD_STANDARD', 'is_policyholder_covered' => 'No', 'cohort' => 'DEP'],
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'CHILD_EXTENDED_BY_POSITION', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'CHILD_ADULT_18PLUS', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'PARENT', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'SIBLING', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'EMPLOYMENT', 'member_classification' => 'DOMESTIC_WORKER', 'is_policyholder_covered' => 'No', 'cohort' => 'DWR'],

            // INVESTOR_PARTNER - No
            ['visa_type' => 'INVESTOR_PARTNER', 'member_classification' => 'SELF', 'is_policyholder_covered' => 'No', 'cohort' => 'INV'],
            ['visa_type' => 'INVESTOR_PARTNER', 'member_classification' => 'SPOUSE', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'INVESTOR_PARTNER', 'member_classification' => 'CHILD_STANDARD', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'INVESTOR_PARTNER', 'member_classification' => 'CHILD_EXTENDED_BY_POSITION', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'INVESTOR_PARTNER', 'member_classification' => 'CHILD_ADULT_18PLUS', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'INVESTOR_PARTNER', 'member_classification' => 'PARENT', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'INVESTOR_PARTNER', 'member_classification' => 'SIBLING', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],

            // GOLDEN_VISA - No
            ['visa_type' => 'GOLDEN_VISA', 'member_classification' => 'SELF', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'GOLDEN_VISA', 'member_classification' => 'SPOUSE', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'GOLDEN_VISA', 'member_classification' => 'CHILD_STANDARD', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'GOLDEN_VISA', 'member_classification' => 'CHILD_EXTENDED_BY_POSITION', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'GOLDEN_VISA', 'member_classification' => 'CHILD_ADULT_18PLUS', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'GOLDEN_VISA', 'member_classification' => 'PARENT', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'GOLDEN_VISA', 'member_classification' => 'SIBLING', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],

            // SELF_EMPLOYED_FREELANCER - No
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'SELF', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'SPOUSE', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'CHILD_STANDARD', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'CHILD_EXTENDED_BY_POSITION', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'CHILD_ADULT_18PLUS', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'PARENT', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'SIBLING', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'SELF_EMPLOYED_FREELANCER', 'member_classification' => 'DOMESTIC_WORKER', 'is_policyholder_covered' => 'No', 'cohort' => 'DWR'],

            // DOMESTIC_WORKER - No
            ['visa_type' => 'DOMESTIC_WORKER', 'member_classification' => 'DOMESTIC_WORKER', 'is_policyholder_covered' => 'No', 'cohort' => 'DWR'],

            // RETIREE - Yes
            ['visa_type' => 'RETIREE', 'member_classification' => 'SELF', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'RETIREE', 'member_classification' => 'SPOUSE', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'RETIREE', 'member_classification' => 'CHILD_STANDARD', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'RETIREE', 'member_classification' => 'CHILD_EXTENDED_BY_POSITION', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'RETIREE', 'member_classification' => 'CHILD_ADULT_18PLUS', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'RETIREE', 'member_classification' => 'PARENT', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],
            ['visa_type' => 'RETIREE', 'member_classification' => 'SIBLING', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],

            // STUDENT - Yes
            ['visa_type' => 'STUDENT', 'member_classification' => 'SELF', 'is_policyholder_covered' => 'Yes', 'cohort' => 'SSD'],

            // RETIREE - No
            ['visa_type' => 'RETIREE', 'member_classification' => 'SELF', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'RETIREE', 'member_classification' => 'SPOUSE', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'RETIREE', 'member_classification' => 'CHILD_STANDARD', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'RETIREE', 'member_classification' => 'CHILD_EXTENDED_BY_POSITION', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'RETIREE', 'member_classification' => 'CHILD_ADULT_18PLUS', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'RETIREE', 'member_classification' => 'PARENT', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
            ['visa_type' => 'RETIREE', 'member_classification' => 'SIBLING', 'is_policyholder_covered' => 'No', 'cohort' => 'SSD'],
        ];

        foreach ($rows as $row) {
            CohortMapping::updateOrCreate(
                [
                    'visa_type' => $row['visa_type'],
                    'member_classification' => $row['member_classification'],
                    'is_policyholder_covered' => $row['is_policyholder_covered'] == 'Yes' ? true : false,
                    'cohort' => $row['cohort'],
                ]
            );
        }
    }
}
