<?php

namespace Database\Seeders;

use App\Enums\RuleEnum;
use App\Enums\RuleTypeEnum;
use App\Models\Rule;
use App\Models\RuleType;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RuleNameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->createRules();
        $this->createRuleType();
    }
    public function createRules()
    {
        try {
            $rules = [
                [
                    'name' => RuleEnum::COMMERCIAL_USE,
                    'rule_type' => RuleTypeEnum::VEHICLE_USE,
                ],
                [
                    'name' => RuleEnum::PRIVATE_USE,
                    'rule_type' => RuleTypeEnum::VEHICLE_USE,
                ],
            ];

            foreach ($rules as $rule) {

                $ruleRecord = Rule::firstOrCreate(
                    ['name' => $rule['name']],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                        'is_active' => 0,
                    ],
                );

                if (! empty($ruleRecord)) {
                    $existingRule = DB::table('rule_details')->where('rule_id', $ruleRecord->id)->first();
                    if (empty($existingRule)) {
                        DB::table('rule_details')->insert(
                            ['rule_id' => $ruleRecord->id, 'created_at' => now(), 'updated_at' => now()]
                        );
                    }
                }
            }
            info('Rule created successfully'.' Company Car ILA'.' Time: '.now());
        } catch (\Exception $e) {
            info('Rule creation failed'.'Company Car ILA'.' Time: '.now());
            info($e->getMessage(), $e->getTrace(), $e->getLine());
        }
    }
    public function createRuleType()
    {
        try {
            RuleType::firstOrCreate(
                ['name' => RuleType::VEHICLE_USE],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
            info('RuleType created successfully'.RuleType::VEHICLE_USE.' Time: '.now());
        } catch (\Exception $e) {
            info('RuleType creation failed'.RuleType::VEHICLE_USE.' Time: '.now());
            info($e->getMessage(), $e->getTrace(), $e->getLine());
        }

    }
}
