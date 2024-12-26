<?php

namespace App\Http\Requests\BuyLeads;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestBuyLeadsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can(PermissionsEnum::BUY_LEADS);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quote_type' => ['required', Rule::enum(QuoteTypes::class)],
            'count' => 'required|numeric|min:1',
        ];
    }

    public function getQuoteType(): QuoteTypes
    {
        return QuoteTypes::from($this->quote_type);
    }

    public function getQuoteTypeId()
    {
        return $this->getQuoteType()->id();
    }
}
