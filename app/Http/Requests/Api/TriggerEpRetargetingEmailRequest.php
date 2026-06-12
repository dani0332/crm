<?php

namespace App\Http\Requests\Api;

use App\Enums\QuoteTypeId;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TriggerEpRetargetingEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $quoteTypeId = (int) $this->input('params.quoteTypeId');
        $quoteTable = $quoteTypeId === QuoteTypeId::Car ? 'car_quote_request' : 'personal_quotes';

        return [
            'params.quoteId' => "required|integer|exists:{$quoteTable},id",
            'params.quoteTypeId' => 'required|integer|in:'.QuoteTypeId::Car.','.QuoteTypeId::Bike,
            'params.embeddedTransactionCode' => 'required|string|exists:embedded_transactions,code',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'params.quoteId.required' => 'Quote ID is required',
            'params.quoteId.integer' => 'Quote ID must be an integer',
            'params.quoteId.exists' => 'The selected quote ID does not exist',
            'params.quoteTypeId.required' => 'Quote type ID is required',
            'params.quoteTypeId.integer' => 'Quote type ID must be an integer',
            'params.quoteTypeId.in' => 'Quote type ID must be Car (1) or Bike (6)',
            'params.embeddedTransactionCode.required' => 'Embedded transaction code is required',
            'params.embeddedTransactionCode.string' => 'Embedded transaction code must be a string',
            'params.embeddedTransactionCode.exists' => 'The selected Embedded transaction code does not exist.',
        ];
    }
}
