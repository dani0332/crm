<?php

namespace App\Http\Requests;

use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SendUpdateLog;
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
            'commission_vat_not_applicable' => 'nullable|numeric',
            'total_vat_amount' => 'sometimes|numeric',
            'price_vat_applicable' => 'sometimes|numeric',
            'price_vat_not_applicable' => 'sometimes|numeric',
            'price_with_vat' => 'required|numeric',
        ];

        $this->sendUpdate = SendUpdateLog::where('id', request()->id ?? '')->firstOrFail();

        $isPriceVatNotApplicableRequired = in_array($this->sendUpdate?->category?->code, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::CPD, SendUpdateLogStatusEnum::CI,
            SendUpdateLogStatusEnum::CIR]) && ($this->sendUpdate->quote_type_id == QuoteTypeId::Life);

        if ($isPriceVatNotApplicableRequired) {
            $rules['price_vat_not_applicable'] = 'required|numeric|min:1';
        } else {
            $rules['price_vat_applicable'] = 'required|numeric|min:1';
            $rules['total_vat_amount'] = 'required|numeric';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'price_vat_not_applicable.min' => 'The price VAT not applicable field is required.',
            'price_vat_applicable.min' => 'The price VAT applicable field is required.',
        ];
    }
}
