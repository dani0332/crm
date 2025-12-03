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
            'brackets.*.min' => ['required', 'numeric', 'min:1'],
            'brackets.*.max' => ['required', 'numeric', 'gte:brackets.*.min'],
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
            'brackets.*.min.required' => 'Minimum amount is required for all brackets.',
            'brackets.*.min.numeric' => 'Minimum amount must be a valid number.',
            'brackets.*.min.min' => 'Minimum amount must be at least 1.',
            'brackets.*.max.required' => 'Maximum amount is required for all brackets.',
            'brackets.*.max.numeric' => 'Maximum amount must be a valid number.',
            'brackets.*.max.gte' => 'Maximum amount must be greater than or equal to minimum amount.',
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

        // Check for overlapping amounts
        if ($this->hasOverlappingBrackets($data['brackets'])) {
            $validator->errors()->add('brackets', "{$this->lobName} brackets have overlapping amount ranges. Please ensure each bracket has a unique range without overlaps.");
        }

        // Check for gaps in amount coverage
        if ($this->hasGapsInBrackets($data['brackets'])) {
            $validator->errors()->add('brackets', "{$this->lobName} brackets have gaps in amount coverage. The maximum of one bracket should be followed by the next bracket starting at max + 1.");
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
        // Check for min and max amounts
        if (! isset($bracket['min']) || ! isset($bracket['max'])) {
            return false;
        }

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
