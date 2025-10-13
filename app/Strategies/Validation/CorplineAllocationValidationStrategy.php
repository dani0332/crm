<?php

declare(strict_types=1);

namespace App\Strategies\Validation;

use App\Contracts\AllocationValidationStrategyInterface;
use App\Models\BusinessTypeOfInsurance;
use App\Models\User;
use App\Traits\ValidatesBracketStructure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CorplineAllocationValidationStrategy implements AllocationValidationStrategyInterface
{
    use ValidatesBracketStructure;

    public function getRules(): array
    {
        return [
            'value_brackets' => ['required', 'array'],
            'value_brackets.*.min' => ['required', 'numeric', 'min:1'],
            'value_brackets.*.max' => ['required', 'numeric', 'gte:value_brackets.*.min'],
            'value_brackets.*.profiles' => ['required_with:value_brackets', 'array', 'min:1'],
            'value_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'value_brackets.*.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'value_brackets.*.profiles.*.businessTypeIds' => ['required', 'array', 'min:1'],
            'value_brackets.*.profiles.*.businessTypeIds.*' => ['integer', Rule::exists(BusinessTypeOfInsurance::class, 'id')],

            'volume_brackets' => ['required', 'array'],
            'volume_brackets.*.min' => ['required', 'numeric', 'min:1'],
            'volume_brackets.*.max' => ['required', 'numeric', 'gte:volume_brackets.*.min'],
            'volume_brackets.*.profiles' => ['required_with:volume_brackets', 'array', 'min:1'],
            'volume_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'volume_brackets.*.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'volume_brackets.*.profiles.*.businessTypeIds' => ['required', 'array', 'min:1'],
            'volume_brackets.*.profiles.*.businessTypeIds.*' => ['integer', Rule::exists(BusinessTypeOfInsurance::class, 'id')],
        ];
    }

    public function getMessages(): array
    {
        return [
            // Value bracket messages
            'value_brackets.required' => 'Value brackets configuration is required.',
            'value_brackets.array' => 'Value brackets must be a valid array.',
            'value_brackets.*.min.required' => 'Minimum amount is required for all value brackets.',
            'value_brackets.*.min.numeric' => 'Minimum amount must be a valid number.',
            'value_brackets.*.min.min' => 'Minimum amount must be at least 1.',
            'value_brackets.*.max.required' => 'Maximum amount is required for all value brackets.',
            'value_brackets.*.max.numeric' => 'Maximum amount must be a valid number.',
            'value_brackets.*.max.gte' => 'Maximum amount must be greater than or equal to minimum amount.',
            'value_brackets.*.profiles.required_with' => 'At least one profile is required for each value bracket.',
            'value_brackets.*.profiles.array' => 'Profiles must be a valid array.',
            'value_brackets.*.profiles.min' => 'Each value bracket must have at least one profile.',
            'value_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'value_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'value_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'value_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'value_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'value_brackets.*.profiles.*.businessTypeIds.required' => 'Please select at least one business type for each profile.',
            'value_brackets.*.profiles.*.businessTypeIds.array' => 'Business type selection must be a valid array.',
            'value_brackets.*.profiles.*.businessTypeIds.min' => 'Please select at least one business type for each profile.',
            'value_brackets.*.profiles.*.businessTypeIds.*.integer' => 'Invalid business type selected.',
            'value_brackets.*.profiles.*.businessTypeIds.*.exists' => 'One or more selected business types do not exist.',

            // Volume bracket messages
            'volume_brackets.required' => 'Volume brackets configuration is required.',
            'volume_brackets.array' => 'Volume brackets must be a valid array.',
            'volume_brackets.*.min.required' => 'Minimum amount is required for all volume brackets.',
            'volume_brackets.*.min.numeric' => 'Minimum amount must be a valid number.',
            'volume_brackets.*.min.min' => 'Minimum amount must be at least 1.',
            'volume_brackets.*.max.required' => 'Maximum amount is required for all volume brackets.',
            'volume_brackets.*.max.numeric' => 'Maximum amount must be a valid number.',
            'volume_brackets.*.max.gte' => 'Maximum amount must be greater than or equal to minimum amount.',
            'volume_brackets.*.profiles.required_with' => 'At least one profile is required for each volume bracket.',
            'volume_brackets.*.profiles.array' => 'Profiles must be a valid array.',
            'volume_brackets.*.profiles.min' => 'Each volume bracket must have at least one profile.',
            'volume_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'volume_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'volume_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'volume_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'volume_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'volume_brackets.*.profiles.*.businessTypeIds.required' => 'Please select at least one business type for each profile.',
            'volume_brackets.*.profiles.*.businessTypeIds.array' => 'Business type selection must be a valid array.',
            'volume_brackets.*.profiles.*.businessTypeIds.min' => 'Please select at least one business type for each profile.',
            'volume_brackets.*.profiles.*.businessTypeIds.*.integer' => 'Invalid business type selected.',
            'volume_brackets.*.profiles.*.businessTypeIds.*.exists' => 'One or more selected business types do not exist.',
        ];
    }

    public function validate(Validator $validator, array $data): void
    {
        // Validate value brackets
        if (isset($data['value_brackets']) && ! empty($data['value_brackets'])) {
            if (! $this->validateCorplineBracketStructure($data['value_brackets'])) {
                $validator->errors()->add('value_brackets', 'Value brackets have invalid structure. Please check all required fields are filled correctly.');
            }

            // Check for overlapping amounts in value brackets
            if ($this->hasOverlappingBrackets($data['value_brackets'])) {
                $validator->errors()->add('value_brackets', 'Value brackets have overlapping amount ranges. Please ensure each bracket has a unique range without overlaps.');
            }

            // Check for gaps in amount coverage in value brackets
            if ($this->hasGapsInBrackets($data['value_brackets'])) {
                $validator->errors()->add('value_brackets', 'Value brackets have gaps in amount coverage. The maximum of one bracket should be followed by the next bracket starting at max + 1.');
            }

            // Validate advisor-business type combinations for value brackets
            foreach ($data['value_brackets'] as $bracketIndex => $bracket) {
                if (isset($bracket['profiles']) && is_array($bracket['profiles'])) {
                    $this->validateProfileCombinations(
                        $bracket['profiles'],
                        $bracketIndex,
                        $validator,
                        'businessTypeIds',
                        'business type'
                    );

                    $this->checkDuplicatesInBracket(
                        $bracket['profiles'],
                        $bracketIndex,
                        $validator,
                        'businessTypeIds',
                        'business type'
                    );
                }
            }
        }

        // Validate volume brackets
        if (isset($data['volume_brackets']) && ! empty($data['volume_brackets'])) {
            if (! $this->validateCorplineBracketStructure($data['volume_brackets'])) {
                $validator->errors()->add('volume_brackets', 'Volume brackets have invalid structure. Please check all required fields are filled correctly.');
            }

            // Check for overlapping amounts in volume brackets
            if ($this->hasOverlappingBrackets($data['volume_brackets'])) {
                $validator->errors()->add('volume_brackets', 'Volume brackets have overlapping amount ranges. Please ensure each bracket has a unique range without overlaps.');
            }

            // Check for gaps in amount coverage in volume brackets
            if ($this->hasGapsInBrackets($data['volume_brackets'])) {
                $validator->errors()->add('volume_brackets', 'Volume brackets have gaps in amount coverage. The maximum of one bracket should be followed by the next bracket starting at max + 1.');
            }

            // Validate advisor-business type combinations for volume brackets
            foreach ($data['volume_brackets'] as $bracketIndex => $bracket) {
                if (isset($bracket['profiles']) && is_array($bracket['profiles'])) {
                    $this->validateProfileCombinations(
                        $bracket['profiles'],
                        $bracketIndex,
                        $validator,
                        'businessTypeIds',
                        'business type'
                    );

                    $this->checkDuplicatesInBracket(
                        $bracket['profiles'],
                        $bracketIndex,
                        $validator,
                        'businessTypeIds',
                        'business type'
                    );
                }
            }
        }

        // Check for at least one bracket configuration
        if ((empty($data['value_brackets']) || count($data['value_brackets']) === 0) &&
            (empty($data['volume_brackets']) || count($data['volume_brackets']) === 0)) {
            $validator->errors()->add('configuration', 'Please configure at least one Value or Volume bracket to save the Corpline allocation configuration.');
        }
    }

    public function getValidatedDefaults(): array
    {
        return [
            'value_brackets' => [],
            'volume_brackets' => [],
        ];
    }

    /**
     * Validate Corpline bracket structure
     */
    private function validateCorplineBracketStructure(array $brackets): bool
    {
        foreach ($brackets as $bracket) {
            // Check for min and max amounts
            if (! isset($bracket['min']) || ! isset($bracket['max'])) {
                return false;
            }

            if (! isset($bracket['profiles']) || ! is_array($bracket['profiles'])) {
                return false;
            }

            foreach ($bracket['profiles'] as $profile) {
                if (! isset($profile['advisorIds']) || ! isset($profile['businessTypeIds'])) {
                    return false;
                }

                if (! is_array($profile['advisorIds']) || ! is_array($profile['businessTypeIds'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check for overlapping amount ranges
     */
    private function hasOverlappingBrackets(array $brackets): bool
    {
        $count = count($brackets);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $min1 = (float) $brackets[$i]['min'];
                $max1 = (float) $brackets[$i]['max'];
                $min2 = (float) $brackets[$j]['min'];
                $max2 = (float) $brackets[$j]['max'];

                // Check if ranges overlap
                if ($min1 <= $max2 && $min2 <= $max1) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check for gaps in amount coverage
     */
    private function hasGapsInBrackets(array $brackets): bool
    {
        if (count($brackets) <= 1) {
            return false;
        }

        // Sort brackets by min amount
        usort($brackets, function ($a, $b) {
            return ((float) $a['min']) <=> ((float) $b['min']);
        });

        // Check for gaps between consecutive brackets
        for ($i = 0; $i < count($brackets) - 1; $i++) {
            $currentMax = (float) $brackets[$i]['max'];
            $nextMin = (float) $brackets[$i + 1]['min'];

            // Allow a gap of 1 (inclusive ranges)
            if ($nextMin > $currentMax + 1) {
                return true;
            }
        }

        return false;
    }
}
