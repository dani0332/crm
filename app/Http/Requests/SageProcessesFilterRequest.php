<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/**
 * Form Request for validating Sage Processes filters
 *
 * Used by both index and export methods in SageProcessesController
 * to validate filter parameters for retrieving failed sage processes.
 */
class SageProcessesFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'insurance_provider_id' => ['nullable', 'array'],
            'insurance_provider_id.*' => ['integer', 'exists:insurance_provider,id'],
            'quote_type_id' => ['nullable', 'array'],
            'quote_type_id.*' => ['required_with:quote_type_id', 'string'],
            'option' => ['nullable', 'string'],
            'lead_status_filter' => ['nullable', 'string'],
            'date_filter_type' => ['nullable', 'array'],
            'date_filter_type.*' => ['string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'insurance_provider_id.array' => 'Insurance provider must be an array.',
            'insurance_provider_id.*.integer' => 'Each insurance provider ID must be an integer.',
            'insurance_provider_id.*.exists' => 'Selected insurance provider does not exist.',
            'quote_type_id.array' => 'Quote type must be an array.',
            'quote_type_id.*.required_with' => 'Quote type ID is required.',
            'quote_type_id.*.string' => 'Each quote type ID must be a string.',
            'option.string' => 'Option must be a string.',
            'lead_status_filter.string' => 'Lead status filter must be a string.',
            'date_filter_type.array' => 'Date filter type must be an array.',
            'date_filter_type.*.string' => 'Each date filter type must be a string.',
            'date_from.date' => 'Start date must be a valid date.',
            'date_to.date' => 'End date must be a valid date.',
            'date_to.after_or_equal' => 'End date must be equal to or after start date.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'insurance_provider_id' => 'insurance provider',
            'quote_type_id' => 'quote type',
            'option' => 'option',
            'lead_status_filter' => 'lead status filter',
            'date_filter_type' => 'date filter type',
            'date_filter_type.*' => 'date filter type value',
            'date_from' => 'start date',
            'date_to' => 'end date',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        LoggerService::warning('SageProcessesFilterRequest - Validation Failed', extra: [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->all(),
        ]);

        parent::failedValidation($validator);
    }
}
