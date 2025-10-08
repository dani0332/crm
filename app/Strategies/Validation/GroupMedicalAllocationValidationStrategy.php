<?php

declare(strict_types=1);

namespace App\Strategies\Validation;

use App\Contracts\AllocationValidationStrategyInterface;
use App\Models\User;
use App\Traits\ValidatesBracketStructure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GroupMedicalAllocationValidationStrategy implements AllocationValidationStrategyInterface
{
    use ValidatesBracketStructure;

    public function getRules(): array
    {
        return [
            'micro_brackets' => ['required', 'array'],
            'micro_brackets.*.employees_min' => ['required_with:micro_brackets', 'numeric', 'min:1'],
            'micro_brackets.*.employees_max' => ['required_with:micro_brackets', 'numeric', 'gte:micro_brackets.*.employees_min'],
            'micro_brackets.*.profiles' => ['required_with:micro_brackets', 'array', 'min:1'],
            'micro_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'micro_brackets.*.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'micro_brackets.*.profiles.*.planTypeIds' => ['required', 'array', 'min:1'],
            'micro_brackets.*.profiles.*.planTypeIds.*' => ['integer'],

            'non_micro_brackets' => ['required', 'array'],
            'non_micro_brackets.*.employees_min' => ['required_with:non_micro_brackets', 'numeric', 'min:1'],
            'non_micro_brackets.*.employees_max' => ['required_with:non_micro_brackets', 'numeric', 'gte:non_micro_brackets.*.employees_min'],
            'non_micro_brackets.*.profiles' => ['required_with:non_micro_brackets', 'array', 'min:1'],
            'non_micro_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'non_micro_brackets.*.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'non_micro_brackets.*.profiles.*.planTypeIds' => ['required', 'array', 'min:1'],
            'non_micro_brackets.*.profiles.*.planTypeIds.*' => ['integer'],
        ];
    }

    public function getMessages(): array
    {
        return [
            // Micro bracket messages
            'micro_brackets.required' => 'Micro brackets configuration is required.',
            'micro_brackets.array' => 'Micro brackets must be a valid array.',
            'micro_brackets.*.employees_min.required_with' => 'Minimum number of employees is required for all micro brackets.',
            'micro_brackets.*.employees_min.numeric' => 'Minimum employees must be a valid number.',
            'micro_brackets.*.employees_min.min' => 'Minimum employees must be at least 1.',
            'micro_brackets.*.employees_max.required_with' => 'Maximum number of employees is required for all micro brackets.',
            'micro_brackets.*.employees_max.numeric' => 'Maximum employees must be a valid number.',
            'micro_brackets.*.employees_max.gte' => 'Maximum employees must be greater than or equal to minimum employees.',
            'micro_brackets.*.profiles.required_with' => 'At least one profile is required for each micro bracket.',
            'micro_brackets.*.profiles.array' => 'Profiles must be a valid array.',
            'micro_brackets.*.profiles.min' => 'Each micro bracket must have at least one profile.',
            'micro_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'micro_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'micro_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'micro_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'micro_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'micro_brackets.*.profiles.*.planTypeIds.required' => 'Please select at least one plan type for each profile.',
            'micro_brackets.*.profiles.*.planTypeIds.array' => 'Plan type selection must be a valid array.',
            'micro_brackets.*.profiles.*.planTypeIds.min' => 'Please select at least one plan type for each profile.',
            'micro_brackets.*.profiles.*.planTypeIds.*.integer' => 'Invalid plan type selected.',

            // Non-Micro bracket messages
            'non_micro_brackets.required' => 'Non-Micro brackets configuration is required.',
            'non_micro_brackets.array' => 'Non-Micro brackets must be a valid array.',
            'non_micro_brackets.*.employees_min.required_with' => 'Minimum number of employees is required for all non-micro brackets.',
            'non_micro_brackets.*.employees_min.numeric' => 'Minimum employees must be a valid number.',
            'non_micro_brackets.*.employees_min.min' => 'Minimum employees must be at least 1.',
            'non_micro_brackets.*.employees_max.required_with' => 'Maximum number of employees is required for all non-micro brackets.',
            'non_micro_brackets.*.employees_max.numeric' => 'Maximum employees must be a valid number.',
            'non_micro_brackets.*.employees_max.gte' => 'Maximum employees must be greater than or equal to minimum employees.',
            'non_micro_brackets.*.profiles.required_with' => 'At least one profile is required for each non-micro bracket.',
            'non_micro_brackets.*.profiles.array' => 'Profiles must be a valid array.',
            'non_micro_brackets.*.profiles.min' => 'Each non-micro bracket must have at least one profile.',
            'non_micro_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'non_micro_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'non_micro_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'non_micro_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'non_micro_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'non_micro_brackets.*.profiles.*.planTypeIds.required' => 'Please select at least one plan type for each profile.',
            'non_micro_brackets.*.profiles.*.planTypeIds.array' => 'Plan type selection must be a valid array.',
            'non_micro_brackets.*.profiles.*.planTypeIds.min' => 'Please select at least one plan type for each profile.',
            'non_micro_brackets.*.profiles.*.planTypeIds.*.integer' => 'Invalid plan type selected.',
        ];
    }

    public function validate(Validator $validator, array $data): void
    {
        // Validate micro brackets
        if (isset($data['micro_brackets']) && ! empty($data['micro_brackets'])) {
            if (! $this->validateGroupMedicalBracketStructure($data['micro_brackets'])) {
                $validator->errors()->add('micro_brackets', 'Micro brackets have invalid structure. Please check all required fields are filled correctly.');
            }

            if ($this->hasOverlappingBrackets($data['micro_brackets'])) {
                $validator->errors()->add('micro_brackets', 'Micro brackets have overlapping employee ranges. Please ensure employee ranges do not overlap.');
            }

            if ($this->hasGapsInBrackets($data['micro_brackets'])) {
                $validator->errors()->add('micro_brackets', 'Micro brackets have gaps in employee ranges. Please ensure continuous coverage.');
            }

            // Validate advisor-planType combinations for micro brackets
            foreach ($data['micro_brackets'] as $bracketIndex => $bracket) {
                if (isset($bracket['profiles']) && is_array($bracket['profiles'])) {
                    $this->validateProfileCombinations(
                        $bracket['profiles'],
                        $bracketIndex,
                        $validator,
                        'planTypeIds',
                        'plan type'
                    );

                    $this->checkDuplicatesInBracket(
                        $bracket['profiles'],
                        $bracketIndex,
                        $validator,
                        'planTypeIds',
                        'plan type'
                    );
                }
            }
        }

        // Validate non-micro brackets
        if (isset($data['non_micro_brackets']) && ! empty($data['non_micro_brackets'])) {
            if (! $this->validateGroupMedicalBracketStructure($data['non_micro_brackets'])) {
                $validator->errors()->add('non_micro_brackets', 'Non-Micro brackets have invalid structure. Please check all required fields are filled correctly.');
            }

            if ($this->hasOverlappingBrackets($data['non_micro_brackets'])) {
                $validator->errors()->add('non_micro_brackets', 'Non-Micro brackets have overlapping employee ranges. Please ensure employee ranges do not overlap.');
            }

            if ($this->hasGapsInBrackets($data['non_micro_brackets'])) {
                $validator->errors()->add('non_micro_brackets', 'Non-Micro brackets have gaps in employee ranges. Please ensure continuous coverage.');
            }

            // Validate advisor-planType combinations for non-micro brackets
            foreach ($data['non_micro_brackets'] as $bracketIndex => $bracket) {
                if (isset($bracket['profiles']) && is_array($bracket['profiles'])) {
                    $this->validateProfileCombinations(
                        $bracket['profiles'],
                        $bracketIndex,
                        $validator,
                        'planTypeIds',
                        'plan type'
                    );

                    $this->checkDuplicatesInBracket(
                        $bracket['profiles'],
                        $bracketIndex,
                        $validator,
                        'planTypeIds',
                        'plan type'
                    );
                }
            }
        }

        // Check for at least one bracket configuration
        if ((empty($data['micro_brackets']) || count($data['micro_brackets']) === 0) &&
            (empty($data['non_micro_brackets']) || count($data['non_micro_brackets']) === 0)) {
            $validator->errors()->add('configuration', 'Please configure at least one Micro or Non-Micro bracket to save the Group Medical allocation configuration.');
        }
    }

    public function getValidatedDefaults(): array
    {
        return [
            'micro_brackets' => [],
            'non_micro_brackets' => [],
        ];
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

