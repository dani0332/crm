<?php

namespace Database\Seeders;

use App\Enums\LeadSourceEnum;
use App\Models\LeadSource;
use App\Models\Rule;
use App\Models\RuleDetail;
use App\Models\RuleLeadSource;
use Illuminate\Database\Seeder;

class CPARulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $leadSource = LeadSource::where('name', LeadSourceEnum::CPA_AUSTRALIA)->first();
        if (empty($leadSource)) {
            $leadSource = LeadSource::create([
                'name' => LeadSourceEnum::CPA_AUSTRALIA,
                'is_active' => 1,
                'is_applicable_for_rules' => 1,
            ]);
        }

        $rule = Rule::where('name', 'CPA Australia')->first();
        if (empty($rule)) {
            // Create CPA Australia rule
            $rule = Rule::create([
                'name' => 'CPA Australia',
                'rule_start_date' => null,
                'rule_end_date' => null,
                'is_active' => 1,
                'rule_type' => 1,
            ]);
        }

        $ruleLeadSource = RuleLeadSource::where('rule_id', $rule->id)->where('lead_source_id', $leadSource->id)->first();
        if (empty($ruleLeadSource)) {
            // Create rule lead source mapping
            RuleLeadSource::create([
                'rule_id' => $rule->id,
                'lead_source_id' => $leadSource->id,
            ]);
        }

        $ruleDetail = RuleDetail::where('rule_id', $rule->id)->where('lead_source_id', $leadSource->id)->first();
        if (empty($ruleDetail)) {
            // Create rule details
            RuleDetail::create([
                'rule_id' => $rule->id,
                'car_make_id' => null,
                'car_model_id' => null,
                'lead_source_id' => $leadSource->id,
            ]);
        }

    }
}
