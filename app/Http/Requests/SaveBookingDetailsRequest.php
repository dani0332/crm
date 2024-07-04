<?php

namespace App\Http\Requests;

use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SendUpdateLog;
use App\Rules\NotZero;
use Illuminate\Foundation\Http\FormRequest;

class SaveBookingDetailsRequest extends FormRequest
{
    protected object $sendUpdate;
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
            'id' => 'required|exists:send_update_logs,id',
            'commission_vat_applicable' => 'required|numeric',
            'invoice_description' => 'required|string',
            'invoice_date' => 'required|date',
            'insurer_tax_invoice_number' => 'required|string',
            'insurer_commission_invoice_number' => 'required|string',
            'discount' => 'nullable|numeric',
            'commission_percentage' => 'required|numeric',
            'commission_vat_not_applicable' => 'nullable|numeric',
            'vat_on_commission' => 'required|numeric',
            'total_commission' => 'required|numeric',            
            'total_vat_amount' => 'sometimes|numeric',
            'price_vat_applicable' => 'sometimes|numeric',
            'price_vat_not_applicable' => 'sometimes|numeric',
            'total_price' => 'sometimes|numeric',
            'price_with_vat' => 'required|numeric',
        ];

        $this->sendUpdate = SendUpdateLog::where('id', request()->id ?? '')->firstOrFail();

        $isPriceVatNotApplicableRequired = in_array($this->sendUpdate?->category?->code, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::CPD, SendUpdateLogStatusEnum::CI,
            SendUpdateLogStatusEnum::CIR]) && ($this->sendUpdate->quote_type_id == QuoteTypeId::Life);

        if ($isPriceVatNotApplicableRequired) {
            $rules['price_vat_not_applicable'] = ['required', 'numeric', new NotZero];
        } else {
            $rules['price_vat_applicable'] = ['required', 'numeric', new NotZero];
            $rules['total_vat_amount'] = 'required|numeric';
        }

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
