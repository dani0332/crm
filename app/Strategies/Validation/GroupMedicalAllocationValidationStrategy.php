<?php

declare(strict_types=1);

namespace App\Strategies\Validation;

use App\Contracts\AllocationValidationStrategyInterface;
use App\Enums\GroupMedicalRegionEnum;
use App\Models\Department;
use App\Models\User;
use App\Traits\ValidatesBracketStructure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GroupMedicalAllocationValidationStrategy implements AllocationValidationStrategyInterface
{
    use ValidatesBracketStructure;

    public function getRules(): array
    {
        return array_merge(
            ...array_map(
                fn (GroupMedicalRegionEnum $region) => $this->buildRegionRules($region->value),
                GroupMedicalRegionEnum::cases(),
            ),
        );
    }

    public function getMessages(): array
    {
        return [
            'auh.required' => 'AUH configuration is required.',
            'auh.array' => 'AUH configuration must be a valid array.',
            'auh.micro_brackets.required' => 'AUH Micro brackets configuration is required.',
            'auh.micro_brackets.array' => 'AUH Micro brackets must be a valid array.',
            'auh.micro_brackets.*.departmentIds.required' => 'AUH Micro brackets require at least one department.',
            'auh.micro_brackets.*.departmentIds.array' => 'AUH Micro bracket departments must be an array.',
            'auh.micro_brackets.*.departmentIds.*.integer' => 'Invalid AUH department selected.',
            'auh.micro_brackets.*.departmentIds.*.exists' => 'One or more AUH departments do not exist.',
            'auh.micro_brackets.*.employees_min.required_with' => 'Minimum number of employees is required for all AUH micro brackets.',
            'auh.micro_brackets.*.employees_min.numeric' => 'Minimum employees must be a valid number for AUH micro brackets.',
            'auh.micro_brackets.*.employees_min.min' => 'Minimum employees must be at least 1 for AUH micro brackets.',
            'auh.micro_brackets.*.employees_min.max' => 'Minimum employees cannot exceed 99999 for AUH micro brackets.',
            'auh.micro_brackets.*.employees_max.required_with' => 'Maximum number of employees is required for all AUH micro brackets.',
            'auh.micro_brackets.*.employees_max.numeric' => 'Maximum employees must be a valid number for AUH micro brackets.',
            'auh.micro_brackets.*.employees_max.gte' => 'Maximum employees must be greater than or equal to minimum employees for AUH micro brackets.',
            'auh.micro_brackets.*.employees_max.max' => 'Maximum employees cannot exceed 99999 for AUH micro brackets.',
            'auh.micro_brackets.*.profiles.required_with' => 'At least one profile is required for each AUH micro bracket.',
            'auh.micro_brackets.*.profiles.array' => 'AUH Micro profiles must be a valid array.',
            'auh.micro_brackets.*.profiles.min' => 'Each AUH micro bracket must have at least one profile.',
            'auh.micro_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each AUH micro profile.',
            'auh.micro_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array for AUH micro profiles.',
            'auh.micro_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each AUH micro profile.',
            'auh.micro_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected for AUH micro profile.',
            'auh.micro_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist for AUH micro profile.',
            'auh.micro_brackets.*.profiles.*.planTypeIds.required' => 'Please select at least one plan type for each AUH micro profile.',
            'auh.micro_brackets.*.profiles.*.planTypeIds.array' => 'Plan type selection must be a valid array for AUH micro profile.',
            'auh.micro_brackets.*.profiles.*.planTypeIds.min' => 'Please select at least one plan type for each AUH micro profile.',
            'auh.micro_brackets.*.profiles.*.planTypeIds.*.integer' => 'Invalid plan type selected for AUH micro profile.',

            'auh.non_micro_brackets.required' => 'AUH Non-Micro brackets configuration is required.',
            'auh.non_micro_brackets.array' => 'AUH Non-Micro brackets must be a valid array.',
            'auh.non_micro_brackets.*.departmentIds.required' => 'AUH Non-Micro brackets require at least one department.',
            'auh.non_micro_brackets.*.departmentIds.array' => 'AUH Non-Micro bracket departments must be an array.',
            'auh.non_micro_brackets.*.departmentIds.*.integer' => 'Invalid AUH department selected.',
            'auh.non_micro_brackets.*.departmentIds.*.exists' => 'One or more AUH departments do not exist.',
            'auh.non_micro_brackets.*.employees_min.required_with' => 'Minimum number of employees is required for all AUH non-micro brackets.',
            'auh.non_micro_brackets.*.employees_min.numeric' => 'Minimum employees must be a valid number for AUH non-micro brackets.',
            'auh.non_micro_brackets.*.employees_min.min' => 'Minimum employees must be at least 1 for AUH non-micro brackets.',
            'auh.non_micro_brackets.*.employees_min.max' => 'Minimum employees cannot exceed 99999 for AUH non-micro brackets.',
            'auh.non_micro_brackets.*.employees_max.required_with' => 'Maximum number of employees is required for all AUH non-micro brackets.',
            'auh.non_micro_brackets.*.employees_max.numeric' => 'Maximum employees must be a valid number for AUH non-micro brackets.',
            'auh.non_micro_brackets.*.employees_max.gte' => 'Maximum employees must be greater than or equal to minimum employees for AUH non-micro brackets.',
            'auh.non_micro_brackets.*.employees_max.max' => 'Maximum employees cannot exceed 99999 for AUH non-micro brackets.',
            'auh.non_micro_brackets.*.profiles.required_with' => 'At least one profile is required for each AUH non-micro bracket.',
            'auh.non_micro_brackets.*.profiles.array' => 'AUH Non-Micro profiles must be a valid array.',
            'auh.non_micro_brackets.*.profiles.min' => 'Each AUH non-micro bracket must have at least one profile.',
            'auh.non_micro_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each AUH non-micro profile.',
            'auh.non_micro_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array for AUH non-micro profiles.',
            'auh.non_micro_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each AUH non-micro profile.',
            'auh.non_micro_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected for AUH non-micro profile.',
            'auh.non_micro_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist for AUH non-micro profile.',
            'auh.non_micro_brackets.*.profiles.*.planTypeIds.required' => 'Please select at least one plan type for each AUH non-micro profile.',
            'auh.non_micro_brackets.*.profiles.*.planTypeIds.array' => 'Plan type selection must be a valid array for AUH non-micro profile.',
            'auh.non_micro_brackets.*.profiles.*.planTypeIds.min' => 'Please select at least one plan type for each AUH non-micro profile.',
            'auh.non_micro_brackets.*.profiles.*.planTypeIds.*.integer' => 'Invalid plan type selected for AUH non-micro profile.',

            'non-auh.required' => 'Non-AUH configuration is required.',
            'non-auh.array' => 'Non-AUH configuration must be a valid array.',
            'non-auh.micro_brackets.required' => 'Non-AUH Micro brackets configuration is required.',
            'non-auh.micro_brackets.array' => 'Non-AUH Micro brackets must be a valid array.',
            'non-auh.micro_brackets.*.departmentIds.required' => 'Non-AUH Micro brackets require at least one department.',
            'non-auh.micro_brackets.*.departmentIds.array' => 'Non-AUH Micro bracket departments must be an array.',
            'non-auh.micro_brackets.*.departmentIds.*.integer' => 'Invalid Non-AUH department selected.',
            'non-auh.micro_brackets.*.departmentIds.*.exists' => 'One or more Non-AUH departments do not exist.',
            'non-auh.micro_brackets.*.employees_min.required_with' => 'Minimum number of employees is required for all Non-AUH micro brackets.',
            'non-auh.micro_brackets.*.employees_min.numeric' => 'Minimum employees must be a valid number for Non-AUH micro brackets.',
            'non-auh.micro_brackets.*.employees_min.min' => 'Minimum employees must be at least 1 for Non-AUH micro brackets.',
            'non-auh.micro_brackets.*.employees_min.max' => 'Minimum employees cannot exceed 99999 for Non-AUH micro brackets.',
            'non-auh.micro_brackets.*.employees_max.required_with' => 'Maximum number of employees is required for all Non-AUH micro brackets.',
            'non-auh.micro_brackets.*.employees_max.numeric' => 'Maximum employees must be a valid number for Non-AUH micro brackets.',
            'non-auh.micro_brackets.*.employees_max.gte' => 'Maximum employees must be greater than or equal to minimum employees for Non-AUH micro brackets.',
            'non-auh.micro_brackets.*.employees_max.max' => 'Maximum employees cannot exceed 99999 for Non-AUH micro brackets.',
            'non-auh.micro_brackets.*.profiles.required_with' => 'At least one profile is required for each Non-AUH micro bracket.',
            'non-auh.micro_brackets.*.profiles.array' => 'Non-AUH Micro profiles must be a valid array.',
            'non-auh.micro_brackets.*.profiles.min' => 'Each Non-AUH micro bracket must have at least one profile.',
            'non-auh.micro_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each Non-AUH micro profile.',
            'non-auh.micro_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array for Non-AUH micro profiles.',
            'non-auh.micro_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each Non-AUH micro profile.',
            'non-auh.micro_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected for Non-AUH micro profile.',
            'non-auh.micro_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist for Non-AUH micro profile.',
            'non-auh.micro_brackets.*.profiles.*.planTypeIds.required' => 'Please select at least one plan type for each Non-AUH micro profile.',
            'non-auh.micro_brackets.*.profiles.*.planTypeIds.array' => 'Plan type selection must be a valid array for Non-AUH micro profile.',
            'non-auh.micro_brackets.*.profiles.*.planTypeIds.min' => 'Please select at least one plan type for each Non-AUH micro profile.',
            'non-auh.micro_brackets.*.profiles.*.planTypeIds.*.integer' => 'Invalid plan type selected for Non-AUH micro profile.',

            'non-auh.non_micro_brackets.required' => 'Non-AUH Non-Micro brackets configuration is required.',
            'non-auh.non_micro_brackets.array' => 'Non-AUH Non-Micro brackets must be a valid array.',
            'non-auh.non_micro_brackets.*.departmentIds.required' => 'Non-AUH Non-Micro brackets require at least one department.',
            'non-auh.non_micro_brackets.*.departmentIds.array' => 'Non-AUH Non-Micro bracket departments must be an array.',
            'non-auh.non_micro_brackets.*.departmentIds.*.integer' => 'Invalid Non-AUH department selected.',
            'non-auh.non_micro_brackets.*.departmentIds.*.exists' => 'One or more Non-AUH departments do not exist.',
            'non-auh.non_micro_brackets.*.employees_min.required_with' => 'Minimum number of employees is required for all Non-AUH non-micro brackets.',
            'non-auh.non_micro_brackets.*.employees_min.numeric' => 'Minimum employees must be a valid number for Non-AUH non-micro brackets.',
            'non-auh.non_micro_brackets.*.employees_min.min' => 'Minimum employees must be at least 1 for Non-AUH non-micro brackets.',
            'non-auh.non_micro_brackets.*.employees_min.max' => 'Minimum employees cannot exceed 99999 for Non-AUH non-micro brackets.',
            'non-auh.non_micro_brackets.*.employees_max.required_with' => 'Maximum number of employees is required for all Non-AUH non-micro brackets.',
            'non-auh.non_micro_brackets.*.employees_max.numeric' => 'Maximum employees must be a valid number for Non-AUH non-micro brackets.',
            'non-auh.non_micro_brackets.*.employees_max.gte' => 'Maximum employees must be greater than or equal to minimum employees for Non-AUH non-micro brackets.',
            'non-auh.non_micro_brackets.*.employees_max.max' => 'Maximum employees cannot exceed 99999 for Non-AUH non-micro brackets.',
            'non-auh.non_micro_brackets.*.profiles.required_with' => 'At least one profile is required for each Non-AUH non-micro bracket.',
            'non-auh.non_micro_brackets.*.profiles.array' => 'Non-AUH Non-Micro profiles must be a valid array.',
            'non-auh.non_micro_brackets.*.profiles.min' => 'Each Non-AUH non-micro bracket must have at least one profile.',
            'non-auh.non_micro_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each Non-AUH non-micro profile.',
            'non-auh.non_micro_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array for Non-AUH non-micro profiles.',
            'non-auh.non_micro_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each Non-AUH non-micro profile.',
            'non-auh.non_micro_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected for Non-AUH non-micro profile.',
            'non-auh.non_micro_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist for Non-AUH non-micro profile.',
            'non-auh.non_micro_brackets.*.profiles.*.planTypeIds.required' => 'Please select at least one plan type for each Non-AUH non-micro profile.',
            'non-auh.non_micro_brackets.*.profiles.*.planTypeIds.array' => 'Plan type selection must be a valid array for Non-AUH non-micro profile.',
            'non-auh.non_micro_brackets.*.profiles.*.planTypeIds.min' => 'Please select at least one plan type for each Non-AUH non-micro profile.',
            'non-auh.non_micro_brackets.*.profiles.*.planTypeIds.*.integer' => 'Invalid plan type selected for Non-AUH non-micro profile.',
        ];
    }

    public function validate(Validator $validator, array $data): void
    {
        $hasAnyRegion = false;

        foreach (GroupMedicalRegionEnum::cases() as $region) {
            $regionKey = $region->value;
            $regionProvided = array_key_exists($regionKey, $data);
            $regionData = $this->resolveRegionConfig($data, $regionKey);
            $hasBrackets = (! empty($regionData['micro_brackets']) && count($regionData['micro_brackets']) > 0) ||
                (! empty($regionData['non_micro_brackets']) && count($regionData['non_micro_brackets']) > 0);

            if (! $regionProvided && ! $hasBrackets) {
                continue;
            }

            $hasAnyRegion = $hasAnyRegion || $hasBrackets;

            $this->validateBracketsForRegion($validator, $regionData, $regionKey, 'micro_brackets', 'planTypeIds', 'plan type');
            $this->validateBracketsForRegion($validator, $regionData, $regionKey, 'non_micro_brackets', 'planTypeIds', 'plan type');
        }

        if (! $hasAnyRegion) {
            $validator->errors()->add('configuration', 'Please configure at least one Micro or Non-Micro bracket for AUH or Non-AUH to save the Group Medical allocation configuration.');
        }
    }

    public function getValidatedDefaults(): array
    {
        return [];
    }

    private function buildRegionRules(string $regionKey): array
    {
        return [
            "{$regionKey}" => ['sometimes', 'array'],
            "{$regionKey}.micro_brackets" => ['sometimes', 'array'],
            "{$regionKey}.micro_brackets.*.departmentIds" => ['sometimes', 'nullable', 'array'],
            "{$regionKey}.micro_brackets.*.departmentIds.*" => ['integer', Rule::exists(Department::class, 'id')],
            "{$regionKey}.micro_brackets.*.employees_min" => ['required_with:'.$regionKey.'.micro_brackets', 'numeric', 'min:1', 'max:99999'],
            "{$regionKey}.micro_brackets.*.employees_max" => ['required_with:'.$regionKey.'.micro_brackets', 'numeric', 'gte:'.$regionKey.'.micro_brackets.*.employees_min', 'max:99999'],
            "{$regionKey}.micro_brackets.*.profiles" => ['required_with:'.$regionKey.'.micro_brackets', 'array', 'min:1'],
            "{$regionKey}.micro_brackets.*.profiles.*.advisorIds" => ['required', 'array', 'min:1'],
            "{$regionKey}.micro_brackets.*.profiles.*.advisorIds.*" => ['integer', Rule::exists(User::class, 'id')],
            "{$regionKey}.micro_brackets.*.profiles.*.planTypeIds" => ['required', 'array', 'min:1'],
            "{$regionKey}.micro_brackets.*.profiles.*.planTypeIds.*" => ['integer'],

            "{$regionKey}.non_micro_brackets" => ['sometimes', 'array'],
            "{$regionKey}.non_micro_brackets.*.departmentIds" => ['sometimes', 'nullable', 'array'],
            "{$regionKey}.non_micro_brackets.*.departmentIds.*" => ['integer', Rule::exists(Department::class, 'id')],
            "{$regionKey}.non_micro_brackets.*.employees_min" => ['required_with:'.$regionKey.'.non_micro_brackets', 'numeric', 'min:1', 'max:99999'],
            "{$regionKey}.non_micro_brackets.*.employees_max" => ['required_with:'.$regionKey.'.non_micro_brackets', 'numeric', 'gte:'.$regionKey.'.non_micro_brackets.*.employees_min', 'max:99999'],
            "{$regionKey}.non_micro_brackets.*.profiles" => ['required_with:'.$regionKey.'.non_micro_brackets', 'array', 'min:1'],
            "{$regionKey}.non_micro_brackets.*.profiles.*.advisorIds" => ['required', 'array', 'min:1'],
            "{$regionKey}.non_micro_brackets.*.profiles.*.advisorIds.*" => ['integer', Rule::exists(User::class, 'id')],
            "{$regionKey}.non_micro_brackets.*.profiles.*.planTypeIds" => ['required', 'array', 'min:1'],
            "{$regionKey}.non_micro_brackets.*.profiles.*.planTypeIds.*" => ['integer'],
        ];
    }

    private function resolveRegionConfig(array $data, string $regionKey): array
    {
        if (isset($data[$regionKey]) && is_array($data[$regionKey])) {
            return $data[$regionKey];
        }

        return [
            'micro_brackets' => [],
            'non_micro_brackets' => [],
        ];
    }

    private function validateBracketsForRegion(
        Validator $validator,
        array $region,
        string $regionKey,
        string $bracketType,
        string $profileKey,
        string $profileLabel
    ): void {
        if (empty($region[$bracketType])) {
            return;
        }

        if (! $this->validateGroupMedicalBracketStructure($region[$bracketType])) {
            $validator->errors()->add(
                "{$regionKey}.{$bracketType}",
                ucfirst($bracketType).' have invalid structure. Please check all required fields are filled correctly.'
            );
        }

        if ($this->hasOverlappingBrackets($region[$bracketType])) {
            $validator->errors()->add(
                "{$regionKey}.{$bracketType}",
                ucfirst($bracketType).' have overlapping employee ranges. Please ensure employee ranges do not overlap.'
            );
        }

        if ($this->hasGapsInBrackets($region[$bracketType])) {
            $validator->errors()->add(
                "{$regionKey}.{$bracketType}",
                ucfirst($bracketType).' have gaps in employee ranges. Please ensure continuous coverage.'
            );
        }

        foreach ($region[$bracketType] as $bracketIndex => $bracket) {
            if (isset($bracket['profiles']) && is_array($bracket['profiles'])) {
                $this->validateProfileCombinations(
                    $bracket['profiles'],
                    $bracketIndex,
                    $validator,
                    $profileKey,
                    $profileLabel
                );

                $this->checkDuplicatesInBracket(
                    $bracket['profiles'],
                    $bracketIndex,
                    $validator,
                    $profileKey,
                    $profileLabel
                );
            }
        }
    }

    /**
     * Validate Group Medical bracket structure
     */
    private function validateGroupMedicalBracketStructure(array $brackets): bool
    {
        foreach ($brackets as $bracket) {
            if (! isset($bracket['employees_min']) || ! isset($bracket['employees_max'])) {
                return false;
            }

            if (! isset($bracket['profiles']) || ! is_array($bracket['profiles'])) {
                return false;
            }

            foreach ($bracket['profiles'] as $profile) {
                if (! isset($profile['advisorIds']) || ! isset($profile['planTypeIds'])) {
                    return false;
                }

                if (! is_array($profile['advisorIds']) || ! is_array($profile['planTypeIds'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check for overlapping employee ranges
     */
    private function hasOverlappingBrackets(array $brackets): bool
    {
        $count = count($brackets);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $min1 = (float) $brackets[$i]['employees_min'];
                $max1 = (float) $brackets[$i]['employees_max'];
                $min2 = (float) $brackets[$j]['employees_min'];
                $max2 = (float) $brackets[$j]['employees_max'];

                // Check if ranges overlap
                if ($min1 <= $max2 && $min2 <= $max1) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check for gaps in employee ranges
     */
    private function hasGapsInBrackets(array $brackets): bool
    {
        if (count($brackets) <= 1) {
            return false;
        }

        // Sort brackets by min amount
        usort($brackets, function ($a, $b) {
            return ((float) $a['employees_min']) <=> ((float) $b['employees_min']);
        });

        // Check for gaps between consecutive brackets
        for ($i = 0; $i < count($brackets) - 1; $i++) {
            $currentMax = (float) $brackets[$i]['employees_max'];
            $nextMin = (float) $brackets[$i + 1]['employees_min'];

            // Allow a gap of 1 (inclusive ranges)
            if ($nextMin > $currentMax + 1) {
                return true;
            }
        }

        return false;
    }
}
