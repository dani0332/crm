<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\QuoteTypes;
use App\Http\Controllers\V2\AMLController;
use App\Support\AmlQuoteAutomation\AmlAutomatableLobRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for IMCRM-triggered AML screening automation.
 *
 * Restricts the {@see AMLController::automateQuoteAmlScreening()}
 * endpoint to LOBs explicitly whitelisted in {@see AmlAutomatableLobRegistry}.
 */
class AutomateQuoteAmlScreeningRequest extends FormRequest
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
            'quoteUuid' => ['required', 'string'],
            'quoteType' => ['required', 'string', Rule::in($this->allowedQuoteTypeValues())],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quoteUuid.required' => 'Quote UUID is required',
            'quoteUuid.string' => 'Quote UUID must be a valid string',
            'quoteType.required' => 'Quote type is required',
            'quoteType.string' => 'Quote type must be a valid string',
            'quoteType.in' => 'AML automation is not supported for this quote type',
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
            'quoteUuid' => 'Quote UUID',
            'quoteType' => 'Quote Type',
        ];
    }

    /**
     * String values of LOBs that are eligible for API-triggered AML automation.
     *
     * @return list<string>
     */
    private function allowedQuoteTypeValues(): array
    {
        return array_map(
            static fn (QuoteTypes $type): string => $type->value,
            AmlAutomatableLobRegistry::allowed()
        );
    }
}
