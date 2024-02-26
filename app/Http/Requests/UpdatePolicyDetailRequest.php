<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePolicyDetailRequest extends FormRequest
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
            'quote_policy_number' => 'required',
            'quote_policy_issuance_date' => 'required',
            'quote_policy_start_date' => 'required',
            'quote_policy_expiry_date' => 'required|date|after:quote_policy_start_date',
            'price_vat_notapplicable' => 'required_without:amount|nullable|decimal:0,9999999999.99',
            'amount' => 'required_without:price_vat_notapplicable|nullable|decimal:0,9999999999.99',
            'amount_with_vat' => 'required',
            'vat' => 'nullable',
            'quote_plan_insurer_quote_number' => 'nullable',
            'quote_policy_issuance_status' => 'nullable',
            'quote_policy_issuance_status_other' => 'nullable',
            'modelType' => 'required',
            'quote_id' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'price_vat_notapplicable.required_without' => 'Price (VAT NOT APPLICABLE) OR Price (VAT APPLICABLE) is required',
            'amount.required_without' => 'Price (VAT NOT APPLICABLE) OR Price (VAT APPLICABLE) is required',

        ];
    }
}
