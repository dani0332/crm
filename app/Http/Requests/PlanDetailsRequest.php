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
            //'price_vat_applicable' => 'required|numeric',
            //'price_vat_not_applicable' => 'required|numeric',
            'price_with_vat' => 'nullable|numeric',
            'insurer_quote_number' => 'required',
        ];

        if (request()->quote_type == quoteTypeCode::Life) {
            $rules['price_vat_not_applicable'] = 'required|numeric';
        } else
        {
            $rules['price_vat_applicable'] = 'required|numeric';
        }

        return $rules;
    }
}
