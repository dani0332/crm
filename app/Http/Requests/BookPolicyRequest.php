<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookPolicyRequest extends FormRequest
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
            'invoice_date' => 'required',
            'insurer_tax_invoice_number' => 'required',
            'insurer_commmission_invoice_number' => 'required',
            'discount' => 'required',
            'commission_percentage' => 'required',
            'broker_invoice_number' => 'required',
            'commission_vat_not_applicable' => 'required_without:commission_vat_applicable',
            'commission_vat_applicable' => 'required_without:commission_vat_not_applicable',
            'total_commission' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'commission_vat_not_applicable.required_without' => 'Commmission (VAT NOT APPLICABLE) OR Commmission (VAT APPLICABLE) is required',
            'commission_vat_applicable.required_without' => 'Commmission (VAT NOT APPLICABLE) OR Commmission (VAT APPLICABLE) is required',

        ];
    }
}
