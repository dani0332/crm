<?php

declare(strict_types=1);

namespace App\Strategies\Validation;

use App\Contracts\AllocationValidationStrategyInterface;
use App\Models\Nationality;
use App\Models\User;
use App\Traits\ValidatesBracketStructure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SimpleAllocationValidationStrategy implements AllocationValidationStrategyInterface
{
    use ValidatesBracketStructure;

    public function __construct(
        private readonly string $lobName = 'Simple'
    ) {
    }

    public function getRules(): array
    {
        return [
            'bracket1' => ['required', 'array'],
            'bracket1.profiles' => ['required', 'array', 'min:1'],
            'bracket1.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'bracket1.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'bracket1.profiles.*.nationalityIds' => ['required', 'array', 'min:1'],
            'bracket1.profiles.*.nationalityIds.*' => ['integer', Rule::exists(Nationality::class, 'id')],
        ];
    }

    public function getMessages(): array
    {
        return [
            'bracket1.required' => "{$this->lobName} bracket configuration is required.",
            'bracket1.array' => "{$this->lobName} bracket must be a valid array.",
            'bracket1.profiles.required' => "At least one profile is required for {$this->lobName} bracket.",
            'bracket1.profiles.array' => 'Profiles must be a valid array.',
            'bracket1.profiles.min' => "{$this->lobName} bracket must have at least one profile.",
            'bracket1.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'bracket1.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'bracket1.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'bracket1.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'bracket1.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'bracket1.profiles.*.nationalityIds.required' => 'Please select at least one nationality for each profile.',
            'bracket1.profiles.*.nationalityIds.array' => 'Nationality selection must be a valid array.',
            'bracket1.profiles.*.nationalityIds.min' => 'Please select at least one nationality for each profile.',
            'bracket1.profiles.*.nationalityIds.*.integer' => 'Invalid nationality selected.',
            'bracket1.profiles.*.nationalityIds.*.exists' => 'One or more selected nationalities do not exist.',
        ];
    }

    public function validate(Validator $validator, array $data): void
    {
        // Validate bracket structure
        if (isset($data['bracket1']) && ! empty($data['bracket1'])) {
            if (! $this->validateBracketStructure($data['bracket1'])) {
                $validator->errors()->add('bracket1', "{$this->lobName} bracket has invalid structure. Please check all required fields are filled correctly.");
            }
        }

        // Check for at least one bracket configuration
        if (empty($data['bracket1']) || empty($data['bracket1']['profiles'])) {
            $validator->errors()->add('configuration', "Please configure at least one profile for {$this->lobName} bracket to save the allocation configuration.");
        }

        // Validate advisor-nationality combinations
        if (isset($data['bracket1']['profiles']) && is_array($data['bracket1']['profiles'])) {
            $this->validateProfileCombinations(
                $data['bracket1']['profiles'],
                0,
                $validator,
                'nationalityIds',
                'nationality'
            );

            $this->checkDuplicatesInBracket(
                $data['bracket1']['profiles'],
                0,
                $validator,
                'nationalityIds',
                'nationality'
            );
        }
    }

    public function getValidatedDefaults(): array
    {
        return [
            'bracket1' => [
                'profiles' => [],
            ],
        ];
    }

    /**
     * Validate bracket structure for simple LOB
     */
    private function validateBracketStructure(array $bracket): bool
    {
        if (! isset($bracket['profiles']) || ! is_array($bracket['profiles'])) {
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

        return true;
    }
}


