<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveBookingDetailsRequest extends FormRequest
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
            'commission_vat_applicable' => 'required|numeric|min:1',
            'invoice_description' => 'required|string',
            'broker_invoice_number' => 'required|string',
            'invoice_date' => 'required|date',
            'insurer_tax_invoice_number' => 'required|string',
            'insurer_commission_invoice_number' => 'required|string',
            'discount' => 'nullable|numeric',
            'commission_percentage' => 'required|numeric',
            'commission_vat_not_applicable' => 'nullable|numeric',
            'vat_on_commission' => 'required|numeric',
            'total_commission' => 'required|numeric',
            'total_vat_amount' => 'required|numeric',
            'price_vat_applicable' => 'required|numeric',
            'price_vat_not_applicable' => 'required|numeric',
            'total_price' => 'required|numeric',
        ];
    }

    /**
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // $validator->errors()->add('error', 'Please upload the Endorsed schedule or Endorsed certificate. ');
        });
    }
}
