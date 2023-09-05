<?php

namespace Database\Seeders;

use App\Models\Rule;
use App\Models\RuleDetail;
use App\Models\RuleLeadSource;
use App\Models\RuleUser;
use Illuminate\Database\Seeder;

class FetchRuleDetailsTableRecordsFromRuleLeadSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $ruleLeadSources = RuleLeadSource::select('lead_source_id', 'user_id', 'rule_id')
            ->get();

        foreach ($ruleLeadSources as $ruleLeadSource) {
            /**
             * update Rule details tables
             */
            $rule = Rule::find($ruleLeadSource->rule_id);

            if (! $rule) {
                continue;
            }

            $ruleDetail = RuleDetail::where('lead_source_id', $ruleLeadSource->lead_source_id)->first();
            if (! $ruleDetail) {
                $rule->ruleDetail()->create([
                    'lead_source_id' => $ruleLeadSource->lead_source_id,
                ]);
            }

            /**
             * update Rule Users tab;e
             */
            $ruleUser = RuleUser::where('rule_id', $ruleLeadSource->rule_id)
                ->where('user_id', $ruleLeadSource->user_id)
                ->first();

            if (! $ruleUser) {
                $rule->ruleUsers()->attach($ruleLeadSource->user_id);
            }
        }
    }
}
