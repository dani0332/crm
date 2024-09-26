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
            'insurer_tax_invoice_number' => 'required|string|max:50',
            'insurer_commission_invoice_number' => 'required|string|max:50',
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
            'broker_invoice_number' => 'required|string',
            'transaction_payment_status' => 'required|string',
            'commission_percentage' => 'required|numeric',
            'vat_on_commission' => 'required|numeric',
            'total_commission' => 'required|numeric',
            'reversal_invoice' => 'sometimes',
        ];

        $this->sendUpdate = SendUpdateLog::where('id', request()->id ?? '')->firstOrFail();

        //        Price not applicable enabled when the endorsement will be Life, Business, Health
        //        Price vat applicable and total vat amount enabled when the endorsement will not be Life

        $validatedCatForPrices = in_array($this->sendUpdate->category?->code, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::CPD, SendUpdateLogStatusEnum::CI,
            SendUpdateLogStatusEnum::CIR]);

        if ($validatedCatForPrices) {
            if (! in_array($this->sendUpdate->quote_type_id, [QuoteTypeId::Life, QuoteTypeId::Business, QuoteTypeId::Health])) {
                $rules['price_vat_applicable'] = ['required', 'numeric', new NotZero];
                $rules['total_vat_amount'] = 'required|numeric';
            }

            if ($this->sendUpdate->quote_type_id == QuoteTypeId::Life) {
                $rules['price_vat_not_applicable'] = ['required', 'numeric', new NotZero];
            }

            if (request()->input('price_vat_applicable') !== 0) {
                $rules['total_vat_amount'] = 'required|numeric';
            }
        }

        if ($this->sendUpdate->category?->code == SendUpdateLogStatusEnum::CPD) {
            $rules['reversal_invoice'] = 'required|string';
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->sendUpdate = SendUpdateLog::where('id', request()->id ?? '')->firstOrFail();

            //        Price not applicable enabled when the endorsement will be Life, Business, Health
            //        Price vat applicable and total vat amount enabled when the endorsement will not be Life

            $validatedCatForPrices = in_array($this->sendUpdate->category?->code, [
                SendUpdateLogStatusEnum::EF,
                SendUpdateLogStatusEnum::CPD,
                SendUpdateLogStatusEnum::CI,
                SendUpdateLogStatusEnum::CIR,
            ]);

            $isAdditionalCommission = $this->sendUpdate->option?->code == SendUpdateLogStatusEnum::ACB;

            if ($validatedCatForPrices && ! $isAdditionalCommission && in_array($this->sendUpdate->quote_type_id, [QuoteTypeId::Business, QuoteTypeId::Health]) &&
                request()->input('price_vat_applicable') == 0 && request()->input('price_vat_not_applicable') == 0) {
                $validator->errors()->add('value', 'One of the fields, either "price vat applicable" or "price vat not applicable", must be greater than 0');
            }
        });
    }
}
