<?php

namespace Database\Seeders;

use App\Models\LeadAllocationRuleProperties;
use Illuminate\Database\Seeder;
class addAllocationRuleProperties extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ruleProperties = LeadAllocationRuleProperties::all()->count();
        if ($ruleProperties == 0) {
            LeadAllocationRuleProperties::insert([
                [
                    'name' => 'source',
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
