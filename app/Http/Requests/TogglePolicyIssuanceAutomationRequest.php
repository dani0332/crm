<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\QuoteTypeId;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for toggling policy issuance automation
 *
 * Validates and handles business logic for enabling/disabling
 * policy issuance automation for Car quotes only.
 */
class TogglePolicyIssuanceAutomationRequest extends FormRequest
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
            'quote_uuid' => 'required|string',
            'quote_type_id' => 'required|integer|in:'.QuoteTypeId::Car,
            'enabled' => 'required|boolean',
        ];
    }

    /**
     * Configure the validator instance with additional business logic validation.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validate quote type is Car
            if (! $this->quote_type_id || $this->quote_type_id != QuoteTypeId::Car) {
                $validator->errors()->add(
                    'quote_type_id',
                    'Policy issuance automation is only available for Car quotes'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quote_uuid.required' => 'Quote UUID is required',
            'quote_uuid.string' => 'Quote UUID must be a valid string',
            'quote_uuid.uuid' => 'Quote UUID must be a valid UUID format',
            'quote_type_id.required' => 'Quote type is required',
            'quote_type_id.integer' => 'Quote type must be a valid integer',
            'quote_type_id.in' => 'Policy issuance automation is only available for Car quotes',
            'enabled.required' => 'Enabled status is required',
            'enabled.boolean' => 'Enabled status must be true or false',
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
            'quote_uuid' => 'Quote UUID',
            'quote_type_id' => 'Quote Type',
            'enabled' => 'automation status',
        ];
    }
}
