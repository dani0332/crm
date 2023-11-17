<?php

namespace App\Http\Requests;

use App\Enums\quoteTypeCode;
use Illuminate\Foundation\Http\FormRequest;

class PlanDetailsRequest extends FormRequest
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
        $rules = [
            'insurance_provider_id' => 'required|integer',
            'price_with_vat' => 'required|numeric',
            'insurer_quote_number' => 'nullable',
        ];

        if (request()->quoteType == quoteTypeCode::Life) {
            $rules['price_vat_not_applicable'] = 'required|numeric';
        } else
        {
            $rules['price_vat_applicable'] = 'required|numeric';
        }

        return $rules;
    }
}
