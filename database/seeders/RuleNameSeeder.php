<?php

namespace Database\Seeders;

use App\Enums\RuleEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enums\RuleTypeEnum;
use Illuminate\Support\Facades\DB;
use App\Models\Rule;

class RuleNameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            [
            'name' => RuleEnum::COMMERCIAL_USE,
            'rule_type' => RuleTypeEnum::VEHICLE_USE,
            ],
            [
            'name' => RuleEnum::PRIVATE_USE,
            'rule_type' => RuleTypeEnum::VEHICLE_USE,
            ]
        ];

        foreach ($rules as $rule) {

            $ruleRecord =  Rule::firstOrCreate(
                ['name' => $rule['name']],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                    'is_active' => 0,
                ],
            );

            if (!empty($ruleRecord)) {
            $existingRule = DB::table('rule_details')->where('rule_id', $ruleRecord->id)->first();
            if (empty($existingRule)) {
                DB::table('rule_details')->insert(
                ['rule_id' => $ruleRecord->id, 'created_at' => now(), 'updated_at' => now()]
                );
            }
            }
        }

    }
}
