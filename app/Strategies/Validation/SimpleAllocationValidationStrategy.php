<?php

declare(strict_types=1);

namespace App\Strategies\Validation;

use App\Contracts\AllocationValidationStrategyInterface;
use App\Enums\QuoteTypes;
use App\Models\User;
use App\Traits\ValidatesBracketStructure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SimpleAllocationValidationStrategy implements AllocationValidationStrategyInterface
{
    use ValidatesBracketStructure;

    private readonly string $lobName;

    public function __construct(
        private readonly QuoteTypes $quoetType
    ) {
        $this->lobName = $this->quoetType->value;
    }

    public function getRules(): array
    {
        return [
            'brackets' => ['required', 'array', 'min:1'],
            'brackets.*.profiles' => ['required', 'array', 'min:1'],
            'brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'brackets.*.profiles.*.advisorIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'brackets.*.profiles.*.teamIds' => ['required', 'array', 'min:1'],
            'brackets.*.profiles.*.teamIds.*' => ['integer'],
        ];
    }

    public function getMessages(): array
    {
        return [
            'brackets.required' => "{$this->lobName} brackets configuration is required.",
            'brackets.array' => "{$this->lobName} brackets must be a valid array.",
            'brackets.min' => "At least one bracket is required for {$this->lobName}.",
            'brackets.*.profiles.required' => "At least one profile is required for each {$this->lobName} bracket.",
            'brackets.*.profiles.array' => 'Profiles must be a valid array.',
            'brackets.*.profiles.min' => "Each {$this->lobName} bracket must have at least one profile.",
            'brackets.*.profiles.*.advisorIds.required' => 'Please select at least one advisor for each profile.',
            'brackets.*.profiles.*.advisorIds.array' => 'Advisor selection must be a valid array.',
            'brackets.*.profiles.*.advisorIds.min' => 'Please select at least one advisor for each profile.',
            'brackets.*.profiles.*.advisorIds.*.integer' => 'Invalid advisor selected.',
            'brackets.*.profiles.*.advisorIds.*.exists' => 'One or more selected advisors do not exist.',
            'brackets.*.profiles.*.teamIds.required' => 'Please select at least one team for each profile.',
            'brackets.*.profiles.*.teamIds.array' => 'Team selection must be a valid array.',
            'brackets.*.profiles.*.teamIds.min' => 'Please select at least one team for each profile.',
            'brackets.*.profiles.*.teamIds.*.integer' => 'Invalid team selected.',
        ];
    }

    public function validate(Validator $validator, array $data): void
    {
        // Check for at least one bracket
        if (empty($data['brackets']) || ! is_array($data['brackets'])) {
            $validator->errors()->add('configuration', "Please configure at least one bracket for {$this->lobName} to save the allocation configuration.");

            return;
        }

        // Validate each bracket structure
        foreach ($data['brackets'] as $bracketIndex => $bracket) {
            if (! $this->validateBracketStructure($bracket)) {
                $validator->errors()->add("brackets.{$bracketIndex}", "{$this->lobName} bracket ".($bracketIndex + 1).' has invalid structure. Please check all required fields are filled correctly.');
            }

            // Validate advisor-team combinations
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

    public function getValidatedDefaults(): array
    {
        return [
            'brackets' => [],
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

        if (empty($bracket['profiles'])) {
            return false;
        }

        foreach ($bracket['profiles'] as $profile) {
            if (! isset($profile['advisorIds']) || ! isset($profile['teamIds'])) {
                return false;
            }

            if (! is_array($profile['advisorIds']) || ! is_array($profile['teamIds'])) {
                return false;
            }

            if (empty($profile['advisorIds']) || empty($profile['teamIds'])) {
                return false;
            }
        }

        return true;
    }
}
