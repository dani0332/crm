<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\QuoteTypes;
use App\Models\Allocation\AllocationConfiguration;
use App\Models\Nationality;
use App\Models\QuoteType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AllocationConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getQuoteType(): QuoteTypes
    {
        return QuoteTypes::from($this->quote_type);
    }

    public function rules(): array
    {
        $rules = [
            'quote_type_id' => ['required', 'integer', Rule::exists(QuoteType::class, 'id')],
            'quote_type' => ['required', Rule::enum(QuoteTypes::class)],

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

        if ($this->route('allocationConfiguration')) {
            $rules['quote_type'] = [
                'required',
                Rule::enum(QuoteTypes::class),
                Rule::unique(AllocationConfiguration::class)
                    ->ignore($this->route('allocationConfiguration'))
                    ->where(function ($query) {
                        return $query->where('quote_type_id', $this->quote_type_id);
                    }),
            ];
        } else {
            $rules['quote_type'] = [
                'required',
                Rule::enum(QuoteTypes::class),
                Rule::unique(AllocationConfiguration::class)
                    ->where(function ($query) {
                        return $query->where('quote_type_id', $this->quote_type_id);
                    }),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'quote_type_id.required' => 'Quote type ID is required.',
            'quote_type_id.integer' => 'Quote type ID must be a valid integer.',
            'quote_type.required' => 'Quote type is required.',
            'quote_type.in' => 'Selected quote type is invalid.',
            'quote_type.unique' => 'Configuration for this quote type already exists.',

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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('lumpsum_brackets') && ! empty($this->lumpsum_brackets)) {
                if (! $this->validateBracketStructure($this->lumpsum_brackets)) {
                    $validator->errors()->add('lumpsum_brackets', 'Lumpsum brackets have invalid structure. Please check all required fields are filled correctly.');
                }

                if ($this->hasOverlappingBrackets($this->lumpsum_brackets)) {
                    $validator->errors()->add('lumpsum_brackets', 'Lumpsum brackets have overlapping amount ranges. Please ensure each bracket has a unique range without overlaps.');
                }

                if ($this->hasGapsInBrackets($this->lumpsum_brackets)) {
                    $validator->errors()->add('lumpsum_brackets', 'Lumpsum brackets have gaps in coverage. Consider adding brackets to cover all amount ranges.');
                }
            }

            if ($this->has('regular_brackets') && ! empty($this->regular_brackets)) {
                if (! $this->validateBracketStructure($this->regular_brackets)) {
                    $validator->errors()->add('regular_brackets', 'Regular brackets have invalid structure. Please check all required fields are filled correctly.');
                }

                if ($this->hasOverlappingBrackets($this->regular_brackets)) {
                    $validator->errors()->add('regular_brackets', 'Regular brackets have overlapping amount ranges. Please ensure each bracket has a unique range without overlaps.');
                }

                if ($this->hasGapsInBrackets($this->regular_brackets)) {
                    $validator->errors()->add('regular_brackets', 'Regular brackets have gaps in coverage. Consider adding brackets to cover all amount ranges.');
                }
            }

            if (empty($this->lumpsum_brackets)) {
                $validator->errors()->add('configuration', 'Please configure at least one lumpsum bracket to save the allocation configuration.');
            }

            if (empty($this->regular_brackets)) {
                $validator->errors()->add('configuration', 'Please configure at least one regular bracket to save the allocation configuration.');
            }

            $this->validateAdvisorNationalityCombinations($validator);
        });
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        $validated['lumpsum_brackets'] = $validated['lumpsum_brackets'] ?? [];
        $validated['regular_brackets'] = $validated['regular_brackets'] ?? [];

        return $validated;
    }

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

    private function hasOverlappingBrackets(array $brackets): bool
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

    private function hasGapsInBrackets(array $brackets): bool
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

    private function validateAdvisorNationalityCombinations($validator): void
    {
        $allBrackets = array_merge(
            $this->input('lumpsum_brackets', []),
            $this->input('regular_brackets', [])
        );

        foreach ($allBrackets as $bracketIndex => $bracket) {
            if (! isset($bracket['profiles']) || ! is_array($bracket['profiles'])) {
                continue;
            }

            foreach ($bracket['profiles'] as $profileIndex => $profile) {
                $advisorIds = $profile['advisorIds'] ?? [];
                $nationalityIds = $profile['nationalityIds'] ?? [];

                if (! is_array($advisorIds) || empty($advisorIds)) {
                    $validator->errors()->add(
                        "bracket_{$bracketIndex}_profile_{$profileIndex}_advisors",
                        'Each profile must have at least one advisor selected.'
                    );
                }

                if (! is_array($nationalityIds) || empty($nationalityIds)) {
                    $validator->errors()->add(
                        "bracket_{$bracketIndex}_profile_{$profileIndex}_nationalities",
                        'Each profile must have at least one nationality selected.'
                    );
                }
            }

            $this->checkDuplicateNationalitiesInBracket($bracket['profiles'], $bracketIndex, $validator);
        }
    }

    private function checkDuplicateNationalitiesInBracket(array $profiles, int $bracketIndex, $validator): void
    {
        $usedNationalities = [];

        foreach ($profiles as $profile) {
            $nationalityIds = $profile['nationalityIds'] ?? [];

            foreach ($nationalityIds as $nationalityId) {
                if (in_array($nationalityId, $usedNationalities)) {
                    $validator->errors()->add(
                        "bracket_{$bracketIndex}_duplicate_nationality",
                        'Each nationality can only be assigned to one profile within the same bracket. Please ensure each nationality appears only once per bracket.'
                    );

                    return;
                }

                $usedNationalities[] = $nationalityId;
            }
        }
    }
}
