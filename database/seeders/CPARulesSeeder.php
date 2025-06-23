<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LeadSource;
use App\Enums\LeadSourceEnum;
use App\Models\Rule;
use App\Models\RuleLeadSource;
use App\Models\RuleDetail;

class CPARulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $leadSource =   LeadSource::firstOrCreate([
            'name' => LeadSourceEnum::CPA_AUSTRALIA,
            'is_active' => 1,
            'is_applicable_for_rules' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        // Create CPA Australia rule
        $rule = Rule::firstOrCreate([
            'name' => 'CPA Australia',
            'rule_start_date' => null,
            'rule_end_date' => null,
            'is_active' => 1,
            'rule_type' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    
        // Create rule lead source mapping
        RuleLeadSource::firstOrCreate([
            'rule_id' => $rule->id,
            'lead_source_id' => $leadSource->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

       
        // Create rule details
        RuleDetail::firstOrCreate([
            'rule_id' => $rule->id,
            'car_make_id' => null,
            'car_model_id' => null,
            'lead_source_id' => $leadSource->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

    }
}
