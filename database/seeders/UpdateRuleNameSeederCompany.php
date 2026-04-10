<?php

namespace Database\Seeders;

use App\Enums\QuoteTypeId;
use App\Enums\RuleEnum;
use App\Enums\RuleTypeEnum;
use App\Models\Rule;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
            $commercialRule = Rule::where('name', RuleEnum::COMMERCIAL_USE->value)
                ->where('rule_type', RuleTypeEnum::VEHICLE_USE)
                ->where('is_active', 1)
                ->first();

            if ($commercialRule) {
                $commercialRule->name = RuleEnum::COMPANY_USE->value;
                $commercialRule->save();
                LoggerService::info('Commercial rule updated successfully for rule name: '.RuleEnum::COMPANY_USE->value.' | Time: '.now());
            } else {
                $commercialRule = Rule::firstOrCreate(
                    [
                        'name' => RuleEnum::COMPANY_USE->value,
                        'rule_type' => RuleTypeEnum::VEHICLE_USE,
                    ],
                    [
                        'is_active' => 1,
                        'quote_type_id' => QuoteTypeId::Car,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                LoggerService::info('Commercial rule created successfully for rule name: '.RuleEnum::COMPANY_USE->value);
            }

            $existingDetail = DB::table('rule_details')->where('rule_id', $commercialRule->id)->first();
            if (empty($existingDetail)) {
                DB::table('rule_details')->insert([
                    'rule_id' => $commercialRule->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

        } catch (\Exception $e) {
            LoggerService::warning('Company Use rule creation failed for rule name: '.RuleEnum::COMPANY_USE->value);
            LoggerService::warning('Exception: '.$e->getMessage().' | Line: '.$e->getLine().' | Trace: '.$e->getFile());

        }
    }
}
