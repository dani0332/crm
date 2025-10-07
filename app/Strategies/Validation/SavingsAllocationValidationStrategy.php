<?php

declare(strict_types=1);

namespace App\Strategies\Validation;

use App\Contracts\AllocationValidationStrategyInterface;
use App\Models\Nationality;
use App\Models\User;
use App\Traits\ValidatesBracketStructure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SavingsAllocationValidationStrategy implements AllocationValidationStrategyInterface
{
    use ValidatesBracketStructure;

    public function getRules(): array
    {
        return [
            'lumpsum_brackets' => ['required', 'array'],
            'lumpsum_brackets.*.min' => ['required_with:lumpsum_brackets', 'numeric', 'min:1'],
            'lumpsum_brackets.*.max' => ['required_with:lumpsum_brackets', 'numeric', 'gte:lumpsum_brackets.*.min'],
            'lumpsum_brackets.*.profiles' => ['required_with:lumpsum_brackets', 'array', 'min:1'],
            'lumpsum_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'lumpsum_brackets.*.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'lumpsum_brackets.*.profiles.*.nationalityIds' => ['required', 'array', 'min:1'],
            'lumpsum_brackets.*.profiles.*.nationalityIds.*' => ['integer', Rule::exists(Nationality::class, 'id')],

            'regular_brackets' => ['required', 'array'],
            'regular_brackets.*.min' => ['required_with:regular_brackets', 'numeric', 'min:1'],
            'regular_brackets.*.max' => ['required_with:regular_brackets', 'numeric', 'gte:regular_brackets.*.min'],
            'regular_brackets.*.profiles' => ['required_with:regular_brackets', 'array', 'min:1'],
            'regular_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'regular_brackets.*.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'regular_brackets.*.profiles.*.nationalityIds' => ['required', 'array', 'min:1'],
            'regular_brackets.*.profiles.*.nationalityIds.*' => ['integer', Rule::exists(Nationality::class, 'id')],
        ];
    }

    public function getMessages(): array
    {
        return [
            // Lumpsum bracket messages
            'lumpsum_brackets.required' => 'Lumpsum brackets configuration is required.',
            'lumpsum_brackets.array' => 'Lumpsum brackets must be a valid array.',
            'lumpsum_brackets.*.min.required_with' => 'Minimum amount is required for all lumpsum brackets.',
            'lumpsum_brackets.*.min.numeric' => 'Minimum amount must be a valid number.',
            'lumpsum_brackets.*.min.min' => 'Minimum lumpsum amount must be at least $1.',
            'lumpsum_brackets.*.max.required_with' => 'Maximum amount is required for all lumpsum brackets.',
            'lumpsum_brackets.*.max.numeric' => 'Maximum amount must be a valid number.',
            'lumpsum_brackets.*.max.gte' => 'Maximum amount must be greater than or equal to minimum amount.',
            'lumpsum_brackets.*.profiles.required_with' => 'At least one profile is required for each lumpsum bracket.',
            'lumpsum_brackets.*.profiles.array' => 'Profiles must be a valid array.',
            'lumpsum_brackets.*.profiles.min' => 'Each lumpsum bracket must have at least one profile.',
            'lumpsum_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'lumpsum_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'lumpsum_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'lumpsum_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'lumpsum_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'lumpsum_brackets.*.profiles.*.nationalityIds.required' => 'Please select at least one nationality for each profile.',
            'lumpsum_brackets.*.profiles.*.nationalityIds.array' => 'Nationality selection must be a valid array.',
            'lumpsum_brackets.*.profiles.*.nationalityIds.min' => 'Please select at least one nationality for each profile.',
            'lumpsum_brackets.*.profiles.*.nationalityIds.*.integer' => 'Invalid nationality selected.',
            'lumpsum_brackets.*.profiles.*.nationalityIds.*.exists' => 'One or more selected nationalities do not exist.',

            // Regular bracket messages
            'regular_brackets.required' => 'Regular brackets configuration is required.',
            'regular_brackets.array' => 'Regular brackets must be a valid array.',
            'regular_brackets.*.min.required_with' => 'Minimum amount is required for all regular brackets.',
            'regular_brackets.*.min.numeric' => 'Minimum amount must be a valid number.',
            'regular_brackets.*.min.min' => 'Minimum regular amount must be at least $1.',
            'regular_brackets.*.max.required_with' => 'Maximum amount is required for all regular brackets.',
            'regular_brackets.*.max.numeric' => 'Maximum amount must be a valid number.',
            'regular_brackets.*.max.gte' => 'Maximum amount must be greater than or equal to minimum amount.',
            'regular_brackets.*.profiles.required_with' => 'At least one profile is required for each regular bracket.',
            'regular_brackets.*.profiles.array' => 'Profiles must be a valid array.',
            'regular_brackets.*.profiles.min' => 'Each regular bracket must have at least one profile.',
            'regular_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'regular_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'regular_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'regular_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'regular_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'regular_brackets.*.profiles.*.nationalityIds.required' => 'Please select at least one nationality for each profile.',
            'regular_brackets.*.profiles.*.nationalityIds.array' => 'Nationality selection must be a valid array.',
            'regular_brackets.*.profiles.*.nationalityIds.min' => 'Please select at least one nationality for each profile.',
            'regular_brackets.*.profiles.*.nationalityIds.*.integer' => 'Invalid nationality selected.',
            'regular_brackets.*.profiles.*.nationalityIds.*.exists' => 'One or more selected nationalities do not exist.',
        ];
    }

    public function validate(Validator $validator, array $data): void
    {
        // Validate lumpsum brackets
        if (isset($data['lumpsum_brackets']) && ! empty($data['lumpsum_brackets'])) {
            if (! $this->validateBracketStructure($data['lumpsum_brackets'])) {
                $validator->errors()->add('lumpsum_brackets', 'Lumpsum brackets have invalid structure. Please check all required fields are filled correctly.');
            }

            if ($this->hasOverlappingBrackets($data['lumpsum_brackets'])) {
                $validator->errors()->add('lumpsum_brackets', 'Lumpsum brackets have overlapping amount ranges. Please ensure each bracket has a unique range without overlaps.');
            }

            if ($this->hasGapsInBrackets($data['lumpsum_brackets'])) {
                $validator->errors()->add('lumpsum_brackets', 'Lumpsum brackets have gaps in coverage. Consider adding brackets to cover all amount ranges.');
            }
        }

        // Validate regular brackets
        if (isset($data['regular_brackets']) && ! empty($data['regular_brackets'])) {
            if (! $this->validateBracketStructure($data['regular_brackets'])) {
                $validator->errors()->add('regular_brackets', 'Regular brackets have invalid structure. Please check all required fields are filled correctly.');
            }

            if ($this->hasOverlappingBrackets($data['regular_brackets'])) {
                $validator->errors()->add('regular_brackets', 'Regular brackets have overlapping amount ranges. Please ensure each bracket has a unique range without overlaps.');
            }

            if ($this->hasGapsInBrackets($data['regular_brackets'])) {
                $validator->errors()->add('regular_brackets', 'Regular brackets have gaps in coverage. Consider adding brackets to cover all amount ranges.');
            }
        }

        // Check for at least one bracket in each type
        if (empty($data['lumpsum_brackets'])) {
            $validator->errors()->add('configuration', 'Please configure at least one lumpsum bracket to save the allocation configuration.');
        }

        if (empty($data['regular_brackets'])) {
            $validator->errors()->add('configuration', 'Please configure at least one regular bracket to save the allocation configuration.');
        }

        // Validate advisor-nationality combinations
        $allBrackets = array_merge(
            $data['lumpsum_brackets'] ?? [],
            $data['regular_brackets'] ?? []
        );

        foreach ($allBrackets as $bracketIndex => $bracket) {
            if (! isset($bracket['profiles']) || ! is_array($bracket['profiles'])) {
                continue;
            }

            $this->validateProfileCombinations(
                $bracket['profiles'],
                $bracketIndex,
                $validator,
                'nationalityIds',
                'nationality'
            );

            $this->checkDuplicatesInBracket(
                $bracket['profiles'],
                $bracketIndex,
                $validator,
                'nationalityIds',
                'nationality'
            );
        }
    }

    public function getValidatedDefaults(): array
    {
        return [
            'lumpsum_brackets' => [],
            'regular_brackets' => [],
        ];
    }

    /**
     * Validate bracket structure for Savings LOB
     */
    private function validateBracketStructure(array $brackets): bool
    {
        foreach ($brackets as $bracket) {
            if (! isset($bracket['min']) || ! isset($bracket['max']) || ! isset($bracket['profiles'])) {
                return false;
            }

            if (! is_numeric($bracket['min']) || ! is_numeric($bracket['max'])) {
                return false;
            }

            if ($bracket['min'] > $bracket['max']) {
                return false;
            }

            foreach ($bracket['profiles'] as $profile) {
                if (! isset($profile['advisorIds']) || ! isset($profile['nationalityIds'])) {
                    return false;
                }

                if (! is_array($profile['advisorIds']) || ! is_array($profile['nationalityIds'])) {
                    return false;
                }
            }
        }

        return true;
    }
}
