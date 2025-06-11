<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\QuoteTypes;
use App\Services\AllocationConfigurationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AllocationConfigurationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'quote_type_id' => ['required', 'integer', 'min:1'],
            'quote_type' => ['required', 'string', Rule::enum(QuoteTypes::class)],
            'lumpsum_brackets' => ['nullable', 'array'],
            'lumpsum_brackets.*.min' => ['required_with:lumpsum_brackets', 'numeric', 'min:0'],
            'lumpsum_brackets.*.max' => ['required_with:lumpsum_brackets', 'numeric', 'gte:lumpsum_brackets.*.min'],
            'lumpsum_brackets.*.profiles' => ['required_with:lumpsum_brackets', 'array', 'min:1'],
            'lumpsum_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'lumpsum_brackets.*.profiles.*.advisorIds.*' => ['integer', 'exists:users,id'],
            'lumpsum_brackets.*.profiles.*.nationalityIds' => ['required', 'array', 'min:1'],
            'lumpsum_brackets.*.profiles.*.nationalityIds.*' => ['integer', 'exists:nationality,id'],

            'regular_brackets' => ['nullable', 'array'],
            'regular_brackets.*.min' => ['required_with:regular_brackets', 'numeric', 'min:0'],
            'regular_brackets.*.max' => ['required_with:regular_brackets', 'numeric', 'gte:regular_brackets.*.min'],
            'regular_brackets.*.profiles' => ['required_with:regular_brackets', 'array', 'min:1'],
            'regular_brackets.*.profiles.*.advisorIds' => ['required', 'array', 'min:1'],
            'regular_brackets.*.profiles.*.advisorIds.*' => ['integer', 'exists:users,id'],
            'regular_brackets.*.profiles.*.nationalityIds' => ['required', 'array', 'min:1'],
            'regular_brackets.*.profiles.*.nationalityIds.*' => ['integer', 'exists:nationality,id'],
        ];

        // Add unique validation for update operations
        if ($this->route('allocationConfiguration')) {
            $rules['quote_type'] = [
                'required',
                'string',
                Rule::enum(QuoteTypes::class),
                Rule::unique('allocation_configurations')
                    ->ignore($this->route('allocationConfiguration'))
                    ->where(function ($query) {
                        return $query->where('quote_type_id', $this->quote_type_id);
                    }),
            ];
        } else {
            $rules['quote_type'] = [
                'required',
                'string',
                Rule::enum(QuoteTypes::class),
                Rule::unique('allocation_configurations')
                    ->where(function ($query) {
                        return $query->where('quote_type_id', $this->quote_type_id);
                    }),
            ];
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
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

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $allocationService = app(AllocationConfigurationService::class);

            // Validate lumpsum brackets structure
            if ($this->has('lumpsum_brackets') && !empty($this->lumpsum_brackets)) {
                if (!$allocationService->validateBracketStructure($this->lumpsum_brackets)) {
                    $validator->errors()->add('lumpsum_brackets', 'Invalid bracket structure for lumpsum brackets.');
                }

                if ($allocationService->hasOverlappingBrackets($this->lumpsum_brackets)) {
                    $validator->errors()->add('lumpsum_brackets', 'Overlapping brackets detected in lumpsum brackets.');
                }
            }

            // Validate regular brackets structure
            if ($this->has('regular_brackets') && !empty($this->regular_brackets)) {
                if (!$allocationService->validateBracketStructure($this->regular_brackets)) {
                    $validator->errors()->add('regular_brackets', 'Invalid bracket structure for regular brackets.');
                }

                if ($allocationService->hasOverlappingBrackets($this->regular_brackets)) {
                    $validator->errors()->add('regular_brackets', 'Overlapping brackets detected in regular brackets.');
                }
            }

            // Ensure at least one bracket type is provided
            if (empty($this->lumpsum_brackets) && empty($this->regular_brackets)) {
                $validator->errors()->add('brackets', 'At least one bracket type (lumpsum or regular) must be configured.');
            }
        });
    }

    /**
     * Get the validated data from the request.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Ensure empty arrays for brackets if not provided
        $validated['lumpsum_brackets'] = $validated['lumpsum_brackets'] ?? [];
        $validated['regular_brackets'] = $validated['regular_brackets'] ?? [];

        return $validated;
    }
}
