<?php

declare(strict_types=1);

namespace App\Strategies\Validation;

use App\Contracts\AllocationValidationStrategyInterface;
use App\Models\User;
use App\Traits\ValidatesBracketStructure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class HomeAllocationValidationStrategy implements AllocationValidationStrategyInterface
{
    use ValidatesBracketStructure;

    public function getRules(): array
    {
        return [
            'value_brackets' => ['required', 'array'],
            'value_brackets.*.contents_min' => ['required_with:value_brackets', 'numeric', 'min:1'],
            'value_brackets.*.contents_max' => ['required_with:value_brackets', 'numeric', 'gte:value_brackets.*.contents_min'],
            'value_brackets.*.building_min' => ['required_with:value_brackets', 'numeric', 'min:1'],
            'value_brackets.*.building_max' => ['required_with:value_brackets', 'numeric', 'gte:value_brackets.*.building_min'],
            'value_brackets.*.profiles' => ['required_with:value_brackets', 'array', 'min:1'],
            'value_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'value_brackets.*.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'value_brackets.*.profiles.*.locations' => ['required', 'array', 'min:1'],
            'value_brackets.*.profiles.*.locations.*' => ['string'],

            'volume_brackets' => ['required', 'array'],
            'volume_brackets.*.contents_min' => ['required_with:volume_brackets', 'numeric', 'min:1'],
            'volume_brackets.*.contents_max' => ['required_with:volume_brackets', 'numeric', 'gte:volume_brackets.*.contents_min'],
            'volume_brackets.*.building_min' => ['required_with:volume_brackets', 'numeric', 'min:1'],
            'volume_brackets.*.building_max' => ['required_with:volume_brackets', 'numeric', 'gte:volume_brackets.*.building_min'],
            'volume_brackets.*.profiles' => ['required_with:volume_brackets', 'array', 'min:1'],
            'volume_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'volume_brackets.*.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'volume_brackets.*.profiles.*.locations' => ['required', 'array', 'min:1'],
            'volume_brackets.*.profiles.*.locations.*' => ['string'],
        ];
    }

    public function getMessages(): array
    {
        return [
            // Value bracket messages
            'value_brackets.required' => 'Value brackets configuration is required.',
            'value_brackets.array' => 'Value brackets must be a valid array.',
            'value_brackets.*.contents_min.required_with' => 'Contents minimum value is required for all value brackets.',
            'value_brackets.*.contents_min.numeric' => 'Contents minimum value must be a valid number.',
            'value_brackets.*.contents_min.min' => 'Contents minimum value must be at least 1 AED.',
            'value_brackets.*.contents_max.required_with' => 'Contents maximum value is required for all value brackets.',
            'value_brackets.*.contents_max.numeric' => 'Contents maximum value must be a valid number.',
            'value_brackets.*.contents_max.gte' => 'Contents maximum value must be greater than or equal to minimum value.',
            'value_brackets.*.building_min.required_with' => 'Building minimum value is required for all value brackets.',
            'value_brackets.*.building_min.numeric' => 'Building minimum value must be a valid number.',
            'value_brackets.*.building_min.min' => 'Building minimum value must be at least 1 AED.',
            'value_brackets.*.building_max.required_with' => 'Building maximum value is required for all value brackets.',
            'value_brackets.*.building_max.numeric' => 'Building maximum value must be a valid number.',
            'value_brackets.*.building_max.gte' => 'Building maximum value must be greater than or equal to minimum value.',
            'value_brackets.*.profiles.required_with' => 'At least one profile is required for each value bracket.',
            'value_brackets.*.profiles.array' => 'Profiles must be a valid array.',
            'value_brackets.*.profiles.min' => 'Each value bracket must have at least one profile.',
            'value_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'value_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'value_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'value_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'value_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'value_brackets.*.profiles.*.locations.required' => 'Please select at least one location for each profile.',
            'value_brackets.*.profiles.*.locations.array' => 'Location selection must be a valid array.',
            'value_brackets.*.profiles.*.locations.min' => 'Please select at least one location for each profile.',

            // Volume bracket messages
            'volume_brackets.required' => 'Volume brackets configuration is required.',
            'volume_brackets.array' => 'Volume brackets must be a valid array.',
            'volume_brackets.*.contents_min.required_with' => 'Contents minimum value is required for all volume brackets.',
            'volume_brackets.*.contents_min.numeric' => 'Contents minimum value must be a valid number.',
            'volume_brackets.*.contents_min.min' => 'Contents minimum value must be at least 1 AED.',
            'volume_brackets.*.contents_max.required_with' => 'Contents maximum value is required for all volume brackets.',
            'volume_brackets.*.contents_max.numeric' => 'Contents maximum value must be a valid number.',
            'volume_brackets.*.contents_max.gte' => 'Contents maximum value must be greater than or equal to minimum value.',
            'volume_brackets.*.building_min.required_with' => 'Building minimum value is required for all volume brackets.',
            'volume_brackets.*.building_min.numeric' => 'Building minimum value must be a valid number.',
            'volume_brackets.*.building_min.min' => 'Building minimum value must be at least 1 AED.',
            'volume_brackets.*.building_max.required_with' => 'Building maximum value is required for all volume brackets.',
            'volume_brackets.*.building_max.numeric' => 'Building maximum value must be a valid number.',
            'volume_brackets.*.building_max.gte' => 'Building maximum value must be greater than or equal to minimum value.',
            'volume_brackets.*.profiles.required_with' => 'At least one profile is required for each volume bracket.',
            'volume_brackets.*.profiles.array' => 'Profiles must be a valid array.',
            'volume_brackets.*.profiles.min' => 'Each volume bracket must have at least one profile.',
            'volume_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'volume_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'volume_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'volume_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'volume_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'volume_brackets.*.profiles.*.locations.required' => 'Please select at least one location for each profile.',
            'volume_brackets.*.profiles.*.locations.array' => 'Location selection must be a valid array.',
            'volume_brackets.*.profiles.*.locations.min' => 'Please select at least one location for each profile.',
        ];
    }

    public function validate(Validator $validator, array $data): void
    {
        // Validate value brackets
        if (isset($data['value_brackets']) && ! empty($data['value_brackets'])) {
            if (! $this->validateBracketStructure($data['value_brackets'])) {
                $validator->errors()->add('value_brackets', 'Value brackets have invalid structure. Please check all required fields are filled correctly.');
            }

            // Check for overlapping Contents values
            if ($this->hasOverlappingRanges($data['value_brackets'], 'contents_min', 'contents_max')) {
                $validator->errors()->add('value_brackets', 'Value brackets have overlapping Contents value ranges. Please ensure each bracket has a unique Contents range without overlaps.');
            }

            // Check for gaps in Contents values
            if ($this->hasGapsInRanges($data['value_brackets'], 'contents_min', 'contents_max')) {
                $validator->errors()->add('value_brackets', 'Value brackets have gaps in Contents value coverage. The maximum of one bracket should be followed by the next bracket starting at max + 1.');
            }

            // Check for overlapping Building values
            if ($this->hasOverlappingRanges($data['value_brackets'], 'building_min', 'building_max')) {
                $validator->errors()->add('value_brackets', 'Value brackets have overlapping Building value ranges. Please ensure each bracket has a unique Building range without overlaps.');
            }

            // Check for gaps in Building values
            if ($this->hasGapsInRanges($data['value_brackets'], 'building_min', 'building_max')) {
                $validator->errors()->add('value_brackets', 'Value brackets have gaps in Building value coverage. The maximum of one bracket should be followed by the next bracket starting at max + 1.');
            }
        }

        // Validate volume brackets
        if (isset($data['volume_brackets']) && ! empty($data['volume_brackets'])) {
            if (! $this->validateBracketStructure($data['volume_brackets'])) {
                $validator->errors()->add('volume_brackets', 'Volume brackets have invalid structure. Please check all required fields are filled correctly.');
            }

            // Check for overlapping Contents values
            if ($this->hasOverlappingRanges($data['volume_brackets'], 'contents_min', 'contents_max')) {
                $validator->errors()->add('volume_brackets', 'Volume brackets have overlapping Contents value ranges. Please ensure each bracket has a unique Contents range without overlaps.');
            }

            // Check for gaps in Contents values
            if ($this->hasGapsInRanges($data['volume_brackets'], 'contents_min', 'contents_max')) {
                $validator->errors()->add('volume_brackets', 'Volume brackets have gaps in Contents value coverage. The maximum of one bracket should be followed by the next bracket starting at max + 1.');
            }

            // Check for overlapping Building values
            if ($this->hasOverlappingRanges($data['volume_brackets'], 'building_min', 'building_max')) {
                $validator->errors()->add('volume_brackets', 'Volume brackets have overlapping Building value ranges. Please ensure each bracket has a unique Building range without overlaps.');
            }

            // Check for gaps in Building values
            if ($this->hasGapsInRanges($data['volume_brackets'], 'building_min', 'building_max')) {
                $validator->errors()->add('volume_brackets', 'Volume brackets have gaps in Building value coverage. The maximum of one bracket should be followed by the next bracket starting at max + 1.');
            }
        }

        // Check for at least one bracket in each type
        if (empty($data['value_brackets'])) {
            $validator->errors()->add('configuration', 'Please configure at least one value bracket to save the allocation configuration.');
        }

        if (empty($data['volume_brackets'])) {
            $validator->errors()->add('configuration', 'Please configure at least one volume bracket to save the allocation configuration.');
        }

        // Validate advisor-location combinations
        $allBrackets = array_merge(
            $data['value_brackets'] ?? [],
            $data['volume_brackets'] ?? []
        );

        foreach ($allBrackets as $bracketIndex => $bracket) {
            if (! isset($bracket['profiles']) || ! is_array($bracket['profiles'])) {
                continue;
            }

            $this->validateProfileCombinations(
                $bracket['profiles'],
                $bracketIndex,
                $validator,
                'locations',
                'location'
            );

            $this->checkDuplicatesInBracket(
                $bracket['profiles'],
                $bracketIndex,
                $validator,
                'locations',
                'location'
            );
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
     * Validate bracket structure for Home LOB
     */
    private function validateBracketStructure(array $brackets): bool
    {
        foreach ($brackets as $bracket) {
            // Check required fields
            if (! isset($bracket['contents_min']) || ! isset($bracket['contents_max']) ||
                ! isset($bracket['building_min']) || ! isset($bracket['building_max']) ||
                ! isset($bracket['profiles'])) {
                return false;
            }

            // Validate numeric fields
            if (! is_numeric($bracket['contents_min']) || ! is_numeric($bracket['contents_max']) ||
                ! is_numeric($bracket['building_min']) || ! is_numeric($bracket['building_max'])) {
                return false;
            }

            // Validate min/max relationships
            if ($bracket['contents_min'] > $bracket['contents_max']) {
                return false;
            }

            if ($bracket['building_min'] > $bracket['building_max']) {
                return false;
            }

            // Validate profiles
            if (! is_array($bracket['profiles']) || empty($bracket['profiles'])) {
                return false;
            }

            foreach ($bracket['profiles'] as $profile) {
                if (! isset($profile['advisorIds']) || ! isset($profile['locations'])) {
                    return false;
                }

                if (! is_array($profile['advisorIds']) || ! is_array($profile['locations'])) {
                    return false;
                }

                if (empty($profile['locations'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if brackets have overlapping ranges for a specific field
     */
    private function hasOverlappingRanges(array $brackets, string $minField, string $maxField): bool
    {
        $count = count($brackets);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $min1 = (float) $brackets[$i][$minField];
                $max1 = (float) $brackets[$i][$maxField];
                $min2 = (float) $brackets[$j][$minField];
                $max2 = (float) $brackets[$j][$maxField];

                // Check if ranges overlap
                if ($min1 <= $max2 && $min2 <= $max1) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if brackets have gaps in coverage for a specific field
     */
    private function hasGapsInRanges(array $brackets, string $minField, string $maxField): bool
    {
        if (count($brackets) <= 1) {
            return false;
        }

        // Sort brackets by min value
        usort($brackets, function ($a, $b) use ($minField) {
            return ((float) $a[$minField]) <=> ((float) $b[$minField]);
        });

        // Check for gaps between consecutive brackets
        for ($i = 0; $i < count($brackets) - 1; $i++) {
            $currentMax = (float) $brackets[$i][$maxField];
            $nextMin = (float) $brackets[$i + 1][$minField];

            // Allow a gap of 1 (inclusive ranges)
            // If max is 10, next min should be 11 (not 12 or higher)
            if ($nextMin > $currentMax + 1) {
                return true;
            }
        }

        return false;
    }
}
