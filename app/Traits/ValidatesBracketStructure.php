<?php

declare(strict_types=1);

namespace App\Traits;

trait ValidatesBracketStructure
{
    /**
     * Check if there are overlapping ranges in brackets
     */
    protected function hasOverlappingBrackets(array $brackets): bool
    {
        $count = count($brackets);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $bracket1 = $brackets[$i];
                $bracket2 = $brackets[$j];

                if (
                    ($bracket1['min'] <= $bracket2['max'] && $bracket1['max'] >= $bracket2['min']) ||
                    ($bracket2['min'] <= $bracket1['max'] && $bracket2['max'] >= $bracket1['min'])
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if there are gaps between bracket ranges
     */
    protected function hasGapsInBrackets(array $brackets): bool
    {
        if (count($brackets) < 2) {
            return false;
        }

        usort($brackets, fn ($a, $b) => $a['min'] <=> $b['min']);

        for ($i = 0; $i < count($brackets) - 1; $i++) {
            $currentMax = $brackets[$i]['max'];
            $nextMin = $brackets[$i + 1]['min'];

            // Check if there's a gap (next min should be currentMax + 1 for consecutive ranges)
            // If nextMin > currentMax + 1, then there's a gap
            if ($nextMin > $currentMax + 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate advisor and secondary field (nationality or location) combinations
     */
    protected function validateProfileCombinations(
        array $profiles,
        int $bracketIndex,
        $validator,
        string $secondaryField,
        string $secondaryFieldLabel
    ): void {
        foreach ($profiles as $profileIndex => $profile) {
            $advisorIds = $profile['advisorIds'] ?? [];
            $secondaryIds = $profile[$secondaryField] ?? [];

            if (! is_array($advisorIds) || empty($advisorIds)) {
                $validator->errors()->add(
                    "bracket_{$bracketIndex}_profile_{$profileIndex}_advisors",
                    'Each profile must have at least one advisor selected.'
                );
            }

            if (! is_array($secondaryIds) || empty($secondaryIds)) {
                $validator->errors()->add(
                    "bracket_{$bracketIndex}_profile_{$profileIndex}_{$secondaryField}",
                    "Each profile must have at least one {$secondaryFieldLabel} selected."
                );
            }
        }
    }

    /**
     * Check for duplicate values in profiles within the same bracket
     */
    protected function checkDuplicatesInBracket(
        array $profiles,
        int $bracketIndex,
        $validator,
        string $field,
        string $fieldLabel
    ): void {
        $usedValues = [];

        foreach ($profiles as $profile) {
            $values = $profile[$field] ?? [];

            foreach ($values as $value) {
                if (in_array($value, $usedValues)) {
                    $validator->errors()->add(
                        "bracket_{$bracketIndex}_duplicate_{$field}",
                        "Each {$fieldLabel} can only be assigned to one profile within the same bracket. Please ensure each {$fieldLabel} appears only once per bracket."
                    );

                    return;
                }

                $usedValues[] = $value;
            }
        }
    }
}
