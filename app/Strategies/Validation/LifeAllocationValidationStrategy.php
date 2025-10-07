<?php

declare(strict_types=1);

namespace App\Strategies\Validation;

use App\Contracts\AllocationValidationStrategyInterface;
use App\Models\Nationality;
use App\Models\User;
use App\Traits\ValidatesBracketStructure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LifeAllocationValidationStrategy implements AllocationValidationStrategyInterface
{
    use ValidatesBracketStructure;

    public function getRules(): array
    {
        return [
            'type1_brackets' => ['required', 'array'],
            'type1_brackets.*.min' => ['required_with:type1_brackets', 'numeric', 'min:1'],
            'type1_brackets.*.max' => ['required_with:type1_brackets', 'numeric', 'gte:type1_brackets.*.min'],
            'type1_brackets.*.profiles' => ['required_with:type1_brackets', 'array', 'min:1'],
            'type1_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'type1_brackets.*.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'type1_brackets.*.profiles.*.nationalityIds' => ['required', 'array', 'min:1'],
            'type1_brackets.*.profiles.*.nationalityIds.*' => ['integer', Rule::exists(Nationality::class, 'id')],

            'type2_brackets' => ['required', 'array'],
            'type2_brackets.*.min' => ['required_with:type2_brackets', 'numeric', 'min:1'],
            'type2_brackets.*.max' => ['required_with:type2_brackets', 'numeric', 'gte:type2_brackets.*.min'],
            'type2_brackets.*.profiles' => ['required_with:type2_brackets', 'array', 'min:1'],
            'type2_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'type2_brackets.*.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'type2_brackets.*.profiles.*.nationalityIds' => ['required', 'array', 'min:1'],
            'type2_brackets.*.profiles.*.nationalityIds.*' => ['integer', Rule::exists(Nationality::class, 'id')],

            'type3_brackets' => ['required', 'array'],
            'type3_brackets.*.min' => ['required_with:type3_brackets', 'numeric', 'min:1'],
            'type3_brackets.*.max' => ['required_with:type3_brackets', 'numeric', 'gte:type3_brackets.*.min'],
            'type3_brackets.*.profiles' => ['required_with:type3_brackets', 'array', 'min:1'],
            'type3_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'type3_brackets.*.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'type3_brackets.*.profiles.*.nationalityIds' => ['required', 'array', 'min:1'],
            'type3_brackets.*.profiles.*.nationalityIds.*' => ['integer', Rule::exists(Nationality::class, 'id')],

            'type4_brackets' => ['required', 'array'],
            'type4_brackets.*.min' => ['required_with:type4_brackets', 'numeric', 'min:1'],
            'type4_brackets.*.max' => ['required_with:type4_brackets', 'numeric', 'gte:type4_brackets.*.min'],
            'type4_brackets.*.profiles' => ['required_with:type4_brackets', 'array', 'min:1'],
            'type4_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'type4_brackets.*.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'type4_brackets.*.profiles.*.nationalityIds' => ['required', 'array', 'min:1'],
            'type4_brackets.*.profiles.*.nationalityIds.*' => ['integer', Rule::exists(Nationality::class, 'id')],
        ];
    }

    public function getMessages(): array
    {
        return [
            // Type 1 bracket messages
            'type1_brackets.required' => 'Type 1 brackets configuration is required.',
            'type1_brackets.array' => 'Type 1 brackets must be a valid array.',
            'type1_brackets.*.min.required_with' => 'Minimum amount is required for all Type 1 brackets.',
            'type1_brackets.*.min.numeric' => 'Minimum amount must be a valid number.',
            'type1_brackets.*.min.min' => 'Minimum amount must be at least $1.',
            'type1_brackets.*.max.required_with' => 'Maximum amount is required for all Type 1 brackets.',
            'type1_brackets.*.max.numeric' => 'Maximum amount must be a valid number.',
            'type1_brackets.*.max.gte' => 'Maximum amount must be greater than or equal to minimum amount.',
            'type1_brackets.*.profiles.required_with' => 'At least one profile is required for each Type 1 bracket.',
            'type1_brackets.*.profiles.array' => 'Profiles must be a valid array.',
            'type1_brackets.*.profiles.min' => 'Each Type 1 bracket must have at least one profile.',
            'type1_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'type1_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'type1_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'type1_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'type1_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'type1_brackets.*.profiles.*.nationalityIds.required' => 'Please select at least one nationality for each profile.',
            'type1_brackets.*.profiles.*.nationalityIds.array' => 'Nationality selection must be a valid array.',
            'type1_brackets.*.profiles.*.nationalityIds.min' => 'Please select at least one nationality for each profile.',
            'type1_brackets.*.profiles.*.nationalityIds.*.integer' => 'Invalid nationality selected.',
            'type1_brackets.*.profiles.*.nationalityIds.*.exists' => 'One or more selected nationalities do not exist.',

            // Type 2 bracket messages
            'type2_brackets.required' => 'Type 2 brackets configuration is required.',
            'type2_brackets.array' => 'Type 2 brackets must be a valid array.',
            'type2_brackets.*.min.required_with' => 'Minimum amount is required for all Type 2 brackets.',
            'type2_brackets.*.min.numeric' => 'Minimum amount must be a valid number.',
            'type2_brackets.*.min.min' => 'Minimum amount must be at least $1.',
            'type2_brackets.*.max.required_with' => 'Maximum amount is required for all Type 2 brackets.',
            'type2_brackets.*.max.numeric' => 'Maximum amount must be a valid number.',
            'type2_brackets.*.max.gte' => 'Maximum amount must be greater than or equal to minimum amount.',
            'type2_brackets.*.profiles.required_with' => 'At least one profile is required for each Type 2 bracket.',
            'type2_brackets.*.profiles.array' => 'Profiles must be a valid array.',
            'type2_brackets.*.profiles.min' => 'Each Type 2 bracket must have at least one profile.',
            'type2_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'type2_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'type2_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'type2_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'type2_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'type2_brackets.*.profiles.*.nationalityIds.required' => 'Please select at least one nationality for each profile.',
            'type2_brackets.*.profiles.*.nationalityIds.array' => 'Nationality selection must be a valid array.',
            'type2_brackets.*.profiles.*.nationalityIds.min' => 'Please select at least one nationality for each profile.',
            'type2_brackets.*.profiles.*.nationalityIds.*.integer' => 'Invalid nationality selected.',
            'type2_brackets.*.profiles.*.nationalityIds.*.exists' => 'One or more selected nationalities do not exist.',

            // Type 3 bracket messages
            'type3_brackets.required' => 'Type 3 brackets configuration is required.',
            'type3_brackets.array' => 'Type 3 brackets must be a valid array.',
            'type3_brackets.*.min.required_with' => 'Minimum amount is required for all Type 3 brackets.',
            'type3_brackets.*.min.numeric' => 'Minimum amount must be a valid number.',
            'type3_brackets.*.min.min' => 'Minimum amount must be at least $1.',
            'type3_brackets.*.max.required_with' => 'Maximum amount is required for all Type 3 brackets.',
            'type3_brackets.*.max.numeric' => 'Maximum amount must be a valid number.',
            'type3_brackets.*.max.gte' => 'Maximum amount must be greater than or equal to minimum amount.',
            'type3_brackets.*.profiles.required_with' => 'At least one profile is required for each Type 3 bracket.',
            'type3_brackets.*.profiles.array' => 'Profiles must be a valid array.',
            'type3_brackets.*.profiles.min' => 'Each Type 3 bracket must have at least one profile.',
            'type3_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'type3_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'type3_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'type3_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'type3_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'type3_brackets.*.profiles.*.nationalityIds.required' => 'Please select at least one nationality for each profile.',
            'type3_brackets.*.profiles.*.nationalityIds.array' => 'Nationality selection must be a valid array.',
            'type3_brackets.*.profiles.*.nationalityIds.min' => 'Please select at least one nationality for each profile.',
            'type3_brackets.*.profiles.*.nationalityIds.*.integer' => 'Invalid nationality selected.',
            'type3_brackets.*.profiles.*.nationalityIds.*.exists' => 'One or more selected nationalities do not exist.',

            // Type 4 bracket messages
            'type4_brackets.required' => 'Type 4 brackets configuration is required.',
            'type4_brackets.array' => 'Type 4 brackets must be a valid array.',
            'type4_brackets.*.min.required_with' => 'Minimum amount is required for all Type 4 brackets.',
            'type4_brackets.*.min.numeric' => 'Minimum amount must be a valid number.',
            'type4_brackets.*.min.min' => 'Minimum amount must be at least $1.',
            'type4_brackets.*.max.required_with' => 'Maximum amount is required for all Type 4 brackets.',
            'type4_brackets.*.max.numeric' => 'Maximum amount must be a valid number.',
            'type4_brackets.*.max.gte' => 'Maximum amount must be greater than or equal to minimum amount.',
            'type4_brackets.*.profiles.required_with' => 'At least one profile is required for each Type 4 bracket.',
            'type4_brackets.*.profiles.array' => 'Profiles must be a valid array.',
            'type4_brackets.*.profiles.min' => 'Each Type 4 bracket must have at least one profile.',
            'type4_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'type4_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'type4_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'type4_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'type4_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'type4_brackets.*.profiles.*.nationalityIds.required' => 'Please select at least one nationality for each profile.',
            'type4_brackets.*.profiles.*.nationalityIds.array' => 'Nationality selection must be a valid array.',
            'type4_brackets.*.profiles.*.nationalityIds.min' => 'Please select at least one nationality for each profile.',
            'type4_brackets.*.profiles.*.nationalityIds.*.integer' => 'Invalid nationality selected.',
            'type4_brackets.*.profiles.*.nationalityIds.*.exists' => 'One or more selected nationalities do not exist.',
        ];
    }

    public function validate(Validator $validator, array $data): void
    {
        $bracketTypes = ['type1_brackets', 'type2_brackets', 'type3_brackets', 'type4_brackets'];

        foreach ($bracketTypes as $bracketType) {
            if (isset($data[$bracketType]) && ! empty($data[$bracketType])) {
                if (! $this->validateBracketStructure($data[$bracketType])) {
                    $label = str_replace('_', ' ', ucfirst($bracketType));
                    $validator->errors()->add($bracketType, "{$label} have invalid structure. Please check all required fields are filled correctly.");
                }

                if ($this->hasOverlappingBrackets($data[$bracketType])) {
                    $label = str_replace('_', ' ', ucfirst($bracketType));
                    $validator->errors()->add($bracketType, "{$label} have overlapping amount ranges. Please ensure each bracket has a unique range without overlaps.");
                }

                if ($this->hasGapsInBrackets($data[$bracketType])) {
                    $label = str_replace('_', ' ', ucfirst($bracketType));
                    $validator->errors()->add($bracketType, "{$label} have gaps in coverage. Consider adding brackets to cover all amount ranges.");
                }
            }
        }

        // Check for at least one bracket in each type
        foreach ($bracketTypes as $bracketType) {
            if (empty($data[$bracketType])) {
                $label = str_replace('_brackets', '', $bracketType);
                $label = str_replace('_', ' ', ucfirst($label));
                $validator->errors()->add('configuration', "Please configure at least one {$label} bracket to save the allocation configuration.");
            }
        }

        // Validate advisor-nationality combinations for all bracket types
        $allBrackets = array_merge(
            $data['type1_brackets'] ?? [],
            $data['type2_brackets'] ?? [],
            $data['type3_brackets'] ?? [],
            $data['type4_brackets'] ?? []
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
            'type1_brackets' => [],
            'type2_brackets' => [],
            'type3_brackets' => [],
            'type4_brackets' => [],
        ];
    }

    /**
     * Validate bracket structure for Life LOB
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

