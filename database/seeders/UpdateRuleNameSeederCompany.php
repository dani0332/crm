<?php

namespace Database\Seeders;

use App\Enums\RuleEnum;
use App\Enums\RuleTypeEnum;
use App\Models\Rule;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Seeder;

class UpdateRuleNameSeederCompany extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createCompanyUseRule();
    }

    private function createCompanyUseRule(): void
    {
        try {
            $commercialRule = Rule::where('name', RuleEnum::COMMERCIAL_USE,
                'rule_type', RuleTypeEnum::VEHICLE_USE,
                'is_active', 1,
            )->first();

            if ($commercialRule) {
                $commercialRule->name = RuleEnum::COMPANY_USE;
                $commercialRule->save();
                LoggerService::info('Commercial rule updated successfully for rule name: '.RuleEnum::COMPANY_USE.' | Time: '.now());
            } else {
                $commercialRule = Rule::firstOrCreate([
                    ['name' => RuleEnum::COMPANY_USE],
                    ['rule_type' => RuleTypeEnum::VEHICLE_USE],
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                LoggerService::info('Commercial rule created successfully for rule name: '.RuleEnum::COMPANY_USE.' | Time: '.now());
            }
        } catch (\Exception $e) {
            LoggerService::warning('Company Use rule creation failed for rule name: '.RuleEnum::COMPANY_USE.' | Time: '.now());
            LoggerService::warning('Exception: '.$e->getMessage().' | Line: '.$e->getLine().' | Trace: '.$e->getFile());

        }
    }
}
