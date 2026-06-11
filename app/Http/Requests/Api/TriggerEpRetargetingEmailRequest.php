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
        $quoteTypeId = (int) $this->input('attributes.data.quoteTypeId');
        $quoteTable = $quoteTypeId === QuoteTypeId::Car ? 'car_quote_request' : 'personal_quotes';

        return [
            'attributes.data.quoteId' => "required|integer|exists:{$quoteTable},id",
            'attributes.data.quoteTypeId' => 'required|integer|in:'.QuoteTypeId::Car.','.QuoteTypeId::Bike,
            'attributes.data.embeddedTransactionCode' => 'required|string|exists:embedded_transactions,code',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attributes.data.quoteId.required' => 'Quote ID is required',
            'attributes.data.quoteId.integer' => 'Quote ID must be an integer',
            'attributes.data.quoteId.exists' => 'The selected quote ID does not exist',
            'attributes.data.quoteTypeId.required' => 'Quote type ID is required',
            'attributes.data.quoteTypeId.integer' => 'Quote type ID must be an integer',
            'attributes.data.quoteTypeId.in' => 'Quote type ID must be Car (1) or Bike (6)',
            'attributes.data.embeddedTransactionCode.required' => 'Embedded transaction code is required',
            'attributes.data.embeddedTransactionCode.string' => 'Embedded transaction code must be a string',
            'attributes.data.embeddedTransactionCode.exists' => 'The selected Embedded transaction code does not exist.',
        ];
    }
}
