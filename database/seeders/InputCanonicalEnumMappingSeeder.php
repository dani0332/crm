<?php

namespace Database\Seeders;

use App\Models\InputCanonicalEnumMapping;
use Illuminate\Database\Seeder;

class InputCanonicalEnumMappingSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Visa Type
            ['input_type' => 'visa_type', 'input_value' => 'Investor / Partner', 'canonical_enum' => 'INVESTOR_PARTNER'],
            ['input_type' => 'visa_type', 'input_value' => 'Golden Visa', 'canonical_enum' => 'GOLDEN_VISA'],
            ['input_type' => 'visa_type', 'input_value' => 'Self Employed / Freelancer', 'canonical_enum' => 'SELF_EMPLOYED_FREELANCER'],
            ['input_type' => 'visa_type', 'input_value' => 'Employment', 'canonical_enum' => 'EMPLOYMENT'],
            ['input_type' => 'visa_type', 'input_value' => 'Dependent / Family', 'canonical_enum' => 'DEPENDENT_FAMILY'],
            ['input_type' => 'visa_type', 'input_value' => 'Domestic Worker', 'canonical_enum' => 'DOMESTIC_WORKER'],

            // Relation Type
            ['input_type' => 'relation_type', 'input_value' => 'Self', 'canonical_enum' => 'SELF'],
            ['input_type' => 'relation_type', 'input_value' => 'Spouse', 'canonical_enum' => 'SPOUSE'],
            ['input_type' => 'relation_type', 'input_value' => 'Child', 'canonical_enum' => 'CHILD'],
            ['input_type' => 'relation_type', 'input_value' => 'Parent', 'canonical_enum' => 'PARENT'],
            ['input_type' => 'relation_type', 'input_value' => 'Sibling', 'canonical_enum' => 'SIBLING'],
            ['input_type' => 'relation_type', 'input_value' => 'Domestic Worker', 'canonical_enum' => 'DOMESTIC_WORKER'],

            // Gender
            ['input_type' => 'gender', 'input_value' => 'Male', 'canonical_enum' => 'MALE'],
            ['input_type' => 'gender', 'input_value' => 'Female', 'canonical_enum' => 'FEMALE'],

            // Marital Status
            ['input_type' => 'marital_status', 'input_value' => 'Single', 'canonical_enum' => 'SINGLE'],
            ['input_type' => 'marital_status', 'input_value' => 'Married', 'canonical_enum' => 'MARRIED'],
            ['input_type' => 'marital_status', 'input_value' => 'Divorced', 'canonical_enum' => 'DIVORCED'],
            ['input_type' => 'marital_status', 'input_value' => 'Widowed', 'canonical_enum' => 'WIDOWED'],
        ];

        foreach ($rows as $row) {
            InputCanonicalEnumMapping::updateOrCreate(
                [
                    'input_type' => $row['input_type'],
                    'input_value' => $row['input_value'],
                    'canonical_enum' => $row['canonical_enum'],
                ]
            );
        }
    }
}
