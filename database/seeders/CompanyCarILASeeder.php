<?php

namespace Database\Seeders;

use App\Enums\RuleEnum;
use App\Enums\RuleTypeEnum;
use App\Models\Rule;
use App\Models\RuleType;
use Illuminate\Database\Seeder;

class CompanyCarILASeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->createRuleType();

    }

    public function createRules()
    {
        try {
            Rule::firstOrCreate(
                ['name' => RuleEnum::COMMERCIAL_USE],
                [
                    'rule_type' => RuleTypeEnum::VEHICLE_USE,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
            Rule::firstOrCreate(
                ['name' => RuleEnum::PRIVATE_USE],
                [
                    'rule_type' => RuleTypeEnum::VEHICLE_USE,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
            info('Rule created successfully'.'Company Car ILA'.' Time: '.now());
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
