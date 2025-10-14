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
            'value_profiles' => ['nullable', 'array'],
            'value_profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'value_profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'value_profiles.*.businessTypeIds' => ['required', 'array', 'min:1'],
            'value_profiles.*.businessTypeIds.*' => ['integer', Rule::exists(BusinessTypeOfInsurance::class, 'id')],

            'volume_profiles' => ['nullable', 'array'],
            'volume_profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'volume_profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'volume_profiles.*.businessTypeIds' => ['required', 'array', 'min:1'],
            'volume_profiles.*.businessTypeIds.*' => ['integer', Rule::exists(BusinessTypeOfInsurance::class, 'id')],
        ];
    }

    public function getMessages(): array
    {
        return [
            // Value profile messages
            'value_profiles.array' => 'Value profiles must be a valid array.',
            'value_profiles.*.advisorIds.required' => 'Please select at least one advisor for each value profile.',
            'value_profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'value_profiles.*.advisorIds.min' => 'Please select at least one advisor for each value profile.',
            'value_profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'value_profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'value_profiles.*.businessTypeIds.required' => 'Please select at least one business type for each value profile.',
            'value_profiles.*.businessTypeIds.array' => 'Business type selection must be a valid array.',
            'value_profiles.*.businessTypeIds.min' => 'Please select at least one business type for each value profile.',
            'value_profiles.*.businessTypeIds.*.integer' => 'Invalid business type selected.',
            'value_profiles.*.businessTypeIds.*.exists' => 'One or more selected business types do not exist.',

            // Volume profile messages
            'volume_profiles.array' => 'Volume profiles must be a valid array.',
            'volume_profiles.*.advisorIds.required' => 'Please select at least one advisor for each volume profile.',
            'volume_profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'volume_profiles.*.advisorIds.min' => 'Please select at least one advisor for each volume profile.',
            'volume_profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'volume_profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'volume_profiles.*.businessTypeIds.required' => 'Please select at least one business type for each volume profile.',
            'volume_profiles.*.businessTypeIds.array' => 'Business type selection must be a valid array.',
            'volume_profiles.*.businessTypeIds.min' => 'Please select at least one business type for each volume profile.',
            'volume_profiles.*.businessTypeIds.*.integer' => 'Invalid business type selected.',
            'volume_profiles.*.businessTypeIds.*.exists' => 'One or more selected business types do not exist.',
        ];
    }

    public function validate(Validator $validator, array $data): void
    {
        // Validate value profiles
        if (isset($data['value_profiles']) && ! empty($data['value_profiles'])) {
            // Validate profile structure
            if (! $this->validateProfileStructure($data['value_profiles'])) {
                $validator->errors()->add('value_profiles', 'Value profiles have invalid structure. Please check all required fields are filled correctly.');
            }

            // Validate advisor-business type combinations
            $this->validateProfileCombinations(
                $data['value_profiles'],
                0,
                $validator,
                'businessTypeIds',
                'business type'
            );

            // Check for duplicate combinations
            $this->checkDuplicateProfiles(
                $data['value_profiles'],
                $validator,
                'value_profiles',
                'businessTypeIds',
                'business type'
            );
        }

        // Validate volume profiles
        if (isset($data['volume_profiles']) && ! empty($data['volume_profiles'])) {
            // Validate profile structure
            if (! $this->validateProfileStructure($data['volume_profiles'])) {
                $validator->errors()->add('volume_profiles', 'Volume profiles have invalid structure. Please check all required fields are filled correctly.');
            }

            // Validate advisor-business type combinations
            $this->validateProfileCombinations(
                $data['volume_profiles'],
                0,
                $validator,
                'businessTypeIds',
                'business type'
            );

            // Check for duplicate combinations
            $this->checkDuplicateProfiles(
                $data['volume_profiles'],
                $validator,
                'volume_profiles',
                'businessTypeIds',
                'business type'
            );
        }

        // Check for at least one profile configuration
        if ((empty($data['value_profiles']) || count($data['value_profiles']) === 0) &&
            (empty($data['volume_profiles']) || count($data['volume_profiles']) === 0)) {
            $validator->errors()->add('configuration', 'Please configure at least one Value or Volume profile to save the Corpline allocation configuration.');
        }
    }

    public function getValidatedDefaults(): array
    {
        return [
            'value_profiles' => [],
            'volume_profiles' => [],
        ];
    }

    /**
     * Validate profile structure
     */
    private function validateProfileStructure(array $profiles): bool
    {
        foreach ($profiles as $profile) {
            if (! isset($profile['advisorIds']) || ! isset($profile['businessTypeIds'])) {
                return false;
            }

            if (! is_array($profile['advisorIds']) || ! is_array($profile['businessTypeIds'])) {
                return false;
            }

            if (empty($profile['advisorIds']) || empty($profile['businessTypeIds'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check for duplicate advisor-business type combinations across all profiles
     */
    private function checkDuplicateProfiles(array $profiles, Validator $validator, string $profileType, string $fieldKey, string $fieldName): void
    {
        $combinations = [];

        foreach ($profiles as $profile) {
            if (! isset($profile['advisorIds']) || ! isset($profile[$fieldKey])) {
                continue;
            }

            foreach ($profile['advisorIds'] as $advisorId) {
                foreach ($profile[$fieldKey] as $fieldId) {
                    $key = "{$advisorId}-{$fieldId}";

                    if (in_array($key, $combinations)) {
                        $validator->errors()->add(
                            $profileType,
                            "Duplicate advisor-{$fieldName} combination found. Each advisor can only be assigned to a {$fieldName} once across all profiles."
                        );

                        return;
                    }

                    $combinations[] = $key;
                }
            }
        }
    }
}
