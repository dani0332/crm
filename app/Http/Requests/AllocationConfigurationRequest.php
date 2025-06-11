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

            'lumpsum_brackets.*.min.required_with' => 'Minimum amount is required for lumpsum brackets.',
            'lumpsum_brackets.*.min.numeric' => 'Minimum amount must be a number.',
            'lumpsum_brackets.*.min.min' => 'Minimum amount cannot be negative.',
            'lumpsum_brackets.*.max.required_with' => 'Maximum amount is required for lumpsum brackets.',
            'lumpsum_brackets.*.max.numeric' => 'Maximum amount must be a number.',
            'lumpsum_brackets.*.max.gte' => 'Maximum amount must be greater than or equal to minimum amount.',
            'lumpsum_brackets.*.profiles.required_with' => 'At least one profile is required for each bracket.',
            'lumpsum_brackets.*.profiles.*.advisorIds.required' => 'Advisor selection is required for each profile.',
            'lumpsum_brackets.*.profiles.*.advisorIds.*.exists' => 'Selected advisor does not exist.',
            'lumpsum_brackets.*.profiles.*.nationalityIds.required' => 'Nationality selection is required for each profile.',
            'lumpsum_brackets.*.profiles.*.nationalityIds.*.exists' => 'Selected nationality does not exist.',

            'regular_brackets.*.min.required_with' => 'Minimum amount is required for regular brackets.',
            'regular_brackets.*.min.numeric' => 'Minimum amount must be a number.',
            'regular_brackets.*.min.min' => 'Minimum amount cannot be negative.',
            'regular_brackets.*.max.required_with' => 'Maximum amount is required for regular brackets.',
            'regular_brackets.*.max.numeric' => 'Maximum amount must be a number.',
            'regular_brackets.*.max.gte' => 'Maximum amount must be greater than or equal to minimum amount.',
            'regular_brackets.*.profiles.required_with' => 'At least one profile is required for each bracket.',
            'regular_brackets.*.profiles.*.advisorIds.required' => 'Advisor selection is required for each profile.',
            'regular_brackets.*.profiles.*.advisorIds.*.exists' => 'Selected advisor does not exist.',
            'regular_brackets.*.profiles.*.nationalityIds.required' => 'Nationality selection is required for each profile.',
            'regular_brackets.*.profiles.*.nationalityIds.*.exists' => 'Selected nationality does not exist.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('lumpsum_brackets') && ! empty($this->lumpsum_brackets)) {
                if (! $this->validateBracketStructure($this->lumpsum_brackets)) {
                    $validator->errors()->add('lumpsum_brackets', 'Invalid bracket structure for lumpsum brackets.');
                }

                if ($this->hasOverlappingBrackets($this->lumpsum_brackets)) {
                    $validator->errors()->add('lumpsum_brackets', 'Overlapping brackets detected in lumpsum brackets.');
                }
            }

            if ($this->has('regular_brackets') && ! empty($this->regular_brackets)) {
                if (! $this->validateBracketStructure($this->regular_brackets)) {
                    $validator->errors()->add('regular_brackets', 'Invalid bracket structure for regular brackets.');
                }

                if ($this->hasOverlappingBrackets($this->regular_brackets)) {
                    $validator->errors()->add('regular_brackets', 'Overlapping brackets detected in regular brackets.');
                }
            }

            if (empty($this->lumpsum_brackets) && empty($this->regular_brackets)) {
                $validator->errors()->add('brackets', 'At least one bracket type (lumpsum or regular) must be configured.');
            }
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
}
