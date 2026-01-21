<?php

namespace App\Http\Requests\Api;

use App\Enums\QuoteTypeId;
use Illuminate\Foundation\Http\FormRequest;

class GetRetargetingEpReminderRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quoteId' => 'required|integer|exists:car_quote_request,id',
            'quoteTypeId' => 'required|integer|in:'.QuoteTypeId::Car,
            'embeddedTransactionCode' => 'required|string',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quoteId.required' => 'Quote ID is required',
            'quoteId.integer' => 'Quote ID must be an integer',
            'quoteId.exists' => 'The selected quote ID does not exist',
            'quoteTypeId.required' => 'Quote type ID is required',
            'quoteTypeId.integer' => 'Quote type ID must be an integer',
            'quoteTypeId.in' => 'Quote type ID must be Car (1)',
            'embeddedTransactionCode.required' => 'Embedded transaction code is required',
            'embeddedTransactionCode.string' => 'Embedded transaction code must be a string',
        ];
    }
}
