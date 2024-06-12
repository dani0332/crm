<?php

namespace App\Http\Requests;

use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SendUpdateLog;
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
        $rules = [
            'commission_vat_applicable' => 'required|numeric',
            'invoice_description' => 'required|string',
            'invoice_date' => 'required|date',
            'insurer_tax_invoice_number' => 'required|string',
            'insurer_commission_invoice_number' => 'required|string',
            'discount' => 'nullable|numeric',
            'commission_vat_not_applicable' => 'nullable|numeric',
            'total_vat_amount' => 'required|numeric',
            'price_vat_applicable' => 'required|numeric',
            'price_vat_not_applicable' => 'required|numeric',
            'total_price' => 'required|numeric',
        ];

        if ($this->get('send_update_option') !== null && $this->get('send_update_option') === SendUpdateLogStatusEnum::ACB) {
            $skipRules = ['insurer_tax_invoice_number', 'total_vat_amount', 'commission_percentage', 'price_vat_applicable', 'price_vat_not_applicable', 'total_price'];
            $rules = array_diff_key($rules, array_flip($skipRules));
        }

        if ($this->get('send_update_option') !== null && $this->get('send_update_option') === SendUpdateLogStatusEnum::ATIB) {
            $skipRules = ['insurer_commission_invoice_number', 'vat_on_commission', 'commission_percentage', 'commission_vat_applicable', 'commission_vat_not_applicable', 'total_commission'];
            $rules = array_diff_key($rules, array_flip($skipRules));
        }

        return $rules;
    }

}
