<?php

namespace Database\Seeders;

use App\Models\RuleType;
use Illuminate\Database\Seeder;

class RuleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ruleTypes = RuleType::RULE_TYPES_LIST;

        foreach ($ruleTypes as $ruleType) {
            RuleType::updateOrCreate(
                ['name' => strtoupper($ruleType)],
                ['name' => strtoupper($ruleType)]
            );
        }
    }
}
