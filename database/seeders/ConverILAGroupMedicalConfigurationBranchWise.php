<?php

namespace Database\Seeders;

use App\Enums\QuoteTypes;
use App\Models\Allocation\AllocationConfiguration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConverILAGroupMedicalConfigurationBranchWise extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $alreadyRan = AllocationConfiguration::query()
            ->whereNotNull('config->auh')
            ->where('quote_type', QuoteTypes::GROUP_MEDICAL)
            ->exists();

        if (! $alreadyRan) {
            DB::transaction(function () {

                $record = AllocationConfiguration::query()->where('quote_type', QuoteTypes::GROUP_MEDICAL)->first();

                if ($record) {
                    $oldConfig = is_array($record->config)
                        ? $record->config
                        : json_decode($record->config, true);

                    // Default empty bracket structure
                    $emptyBracket = [
                        [
                            'profiles' => [
                                [
                                    'advisorIds' => [],
                                    'planTypeIds' => [],
                                ],
                            ],
                            'departmentIds' => [],
                            'employees_max' => 0,
                            'employees_min' => 0,
                        ],
                    ];

                    // If old config NOT exists → create empty for both
                    if (empty($oldConfig)) {

                        $newConfig = [
                            'non-auh' => [
                                'micro_brackets' => $emptyBracket,
                                'non_micro_brackets' => $emptyBracket,
                            ],
                            'auh' => [
                                'micro_brackets' => $emptyBracket,
                                'non_micro_brackets' => $emptyBracket,
                            ],
                        ];

                    } else {

                        // Add departmentIds to existing brackets
                        $addDepartmentIds = function ($brackets) {
                            return collect($brackets)->map(function ($item) {
                                $item['departmentIds'] = $item['departmentIds'] ?? [];

                                return $item;
                            })->toArray();
                        };

                        $newConfig = [
                            'non-auh' => [
                                'micro_brackets' => isset($oldConfig['micro_brackets'])
                                        ? $addDepartmentIds($oldConfig['micro_brackets'])
                                        : [],
                                'non_micro_brackets' => isset($oldConfig['non_micro_brackets'])
                                        ? $addDepartmentIds($oldConfig['non_micro_brackets'])
                                        : [],
                            ],
                            'auh' => [
                                'micro_brackets' => $emptyBracket,
                                'non_micro_brackets' => $emptyBracket,
                            ],
                        ];
                    }

                    $record->update([
                        'config' => $newConfig,
                    ]);
                }

            });
        }
    }
}
