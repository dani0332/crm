<?php

namespace App\Http\Requests;

use App\Enums\QuoteTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FetchAllocationConfigurationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function getQuoteType(): QuoteTypes
    {
        return QuoteTypes::from($this->quote_type);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quote_type' => [Rule::enum(QuoteTypes::class)],
        ];
    }
}
