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
        return [
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
    }

    /**
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $priceVatApplicable = abs($this->get('price_vat_applicable'));
            $priceVatNotApplicable = abs($this->get('price_vat_not_applicable'));
            $totalVatAmount = abs($this->get('total_vat_amount'));
            $this->sendUpdate = SendUpdateLog::where('id', request()->id ?? '')->firstOrFail();

            $isPriceVatNotApplicableRequired = in_array($this->sendUpdate?->category?->code, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::CPD, SendUpdateLogStatusEnum::CI,
                SendUpdateLogStatusEnum::CIR]) && ($this->sendUpdate->quote_type_id == QuoteTypeId::Life) && ($priceVatNotApplicable < 1);

            if ($isPriceVatNotApplicableRequired) {
                return $validator->errors()->add('error', 'Price vat not applicable required.');
            } elseif ($priceVatApplicable < 1 && $priceVatNotApplicable < 1) {
                return $validator->errors()->add('error', 'Price vat applicable required.');
            }

            if ($priceVatApplicable > 0 && ($totalVatAmount < 1)) {
                return $validator->errors()->add('error', 'Total Vat amount required.');
            }
        });
    }
}
