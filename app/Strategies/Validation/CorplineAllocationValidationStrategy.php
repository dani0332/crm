<?php

declare(strict_types=1);

namespace App\Strategies\Validation;

use App\Contracts\AllocationValidationStrategyInterface;
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
            'value_brackets.*.profiles' => ['required_with:value_brackets', 'array', 'min:1'],
            'value_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'value_brackets.*.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'value_brackets.*.profiles.*.teamIds' => ['required', 'array', 'min:1'],
            'value_brackets.*.profiles.*.teamIds.*' => ['integer'],

            'volume_brackets' => ['required', 'array'],
            'volume_brackets.*.profiles' => ['required_with:volume_brackets', 'array', 'min:1'],
            'volume_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'volume_brackets.*.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'volume_brackets.*.profiles.*.teamIds' => ['required', 'array', 'min:1'],
            'volume_brackets.*.profiles.*.teamIds.*' => ['integer'],
        ];
    }

    public function getMessages(): array
    {
        return [
            // Value bracket messages
            'value_brackets.required' => 'Value brackets configuration is required.',
            'value_brackets.array' => 'Value brackets must be a valid array.',
            'value_brackets.*.profiles.required_with' => 'At least one profile is required for each value bracket.',
            'value_brackets.*.profiles.array' => 'Profiles must be a valid array.',
            'value_brackets.*.profiles.min' => 'Each value bracket must have at least one profile.',
            'value_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'value_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'value_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'value_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'value_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'value_brackets.*.profiles.*.teamIds.required' => 'Please select at least one team for each profile.',
            'value_brackets.*.profiles.*.teamIds.array' => 'Team selection must be a valid array.',
            'value_brackets.*.profiles.*.teamIds.min' => 'Please select at least one team for each profile.',
            'value_brackets.*.profiles.*.teamIds.*.integer' => 'Invalid team selected.',

            // Volume bracket messages
            'volume_brackets.required' => 'Volume brackets configuration is required.',
            'volume_brackets.array' => 'Volume brackets must be a valid array.',
            'volume_brackets.*.profiles.required_with' => 'At least one profile is required for each volume bracket.',
            'volume_brackets.*.profiles.array' => 'Profiles must be a valid array.',
            'volume_brackets.*.profiles.min' => 'Each volume bracket must have at least one profile.',
            'volume_brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'volume_brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'volume_brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'volume_brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'volume_brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'volume_brackets.*.profiles.*.teamIds.required' => 'Please select at least one team for each profile.',
            'volume_brackets.*.profiles.*.teamIds.array' => 'Team selection must be a valid array.',
            'volume_brackets.*.profiles.*.teamIds.min' => 'Please select at least one team for each profile.',
            'volume_brackets.*.profiles.*.teamIds.*.integer' => 'Invalid team selected.',
        ];
    }

    public function validate(Validator $validator, array $data): void
    {
        // Validate value brackets
        if (isset($data['value_brackets']) && ! empty($data['value_brackets'])) {
            if (! $this->validateCorplineBracketStructure($data['value_brackets'])) {
                $validator->errors()->add('value_brackets', 'Value brackets have invalid structure. Please check all required fields are filled correctly.');
            }

            // Validate advisor-team combinations for value brackets
            foreach ($data['value_brackets'] as $bracketIndex => $bracket) {
                if (isset($bracket['profiles']) && is_array($bracket['profiles'])) {
                    $this->validateProfileCombinations(
                        $bracket['profiles'],
                        $bracketIndex,
                        $validator,
                        'teamIds',
                        'team'
                    );

                    $this->checkDuplicatesInBracket(
                        $bracket['profiles'],
                        $bracketIndex,
                        $validator,
                        'teamIds',
                        'team'
                    );
                }
            }
        }

        // Validate volume brackets
        if (isset($data['volume_brackets']) && ! empty($data['volume_brackets'])) {
            if (! $this->validateCorplineBracketStructure($data['volume_brackets'])) {
                $validator->errors()->add('volume_brackets', 'Volume brackets have invalid structure. Please check all required fields are filled correctly.');
            }

            // Validate advisor-team combinations for volume brackets
            foreach ($data['volume_brackets'] as $bracketIndex => $bracket) {
                if (isset($bracket['profiles']) && is_array($bracket['profiles'])) {
                    $this->validateProfileCombinations(
                        $bracket['profiles'],
                        $bracketIndex,
                        $validator,
                        'teamIds',
                        'team'
                    );

                    $this->checkDuplicatesInBracket(
                        $bracket['profiles'],
                        $bracketIndex,
                        $validator,
                        'teamIds',
                        'team'
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
            if (! isset($bracket['profiles']) || ! is_array($bracket['profiles'])) {
                return false;
            }

            foreach ($bracket['profiles'] as $profile) {
                if (! isset($profile['advisorIds']) || ! isset($profile['teamIds'])) {
                    return false;
                }

                if (! is_array($profile['advisorIds']) || ! is_array($profile['teamIds'])) {
                    return false;
                }
            }
        }

        return true;
    }
}

