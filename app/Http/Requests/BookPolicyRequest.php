<?php

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Payment;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

class BookPolicyRequest extends FormRequest
{
    use GenericQueriesAllLobs;
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
            'insurer_tax_invoice_number' => 'required|max:50',
            'insurer_commmission_invoice_number' => 'required|max:50|different:insurer_tax_invoice_number',
            'discount' => 'nullable',
            'transaction_payment_status' => 'nullable',
            'broker_invoice_number' => 'nullable',
            'commission_vat_not_applicable' => 'required_without:commission_vat_applicable|nullable|numeric|between:0,9999999.99',
            'commission_vat_applicable' => 'required|numeric|between:0,9999999.99',
            'total_commission' => 'nullable|numeric|between:0,9999999.99',
            'invoice_description' => 'required|max:60',
            'vat_on_commission' => 'nullable|numeric|between:0,9999999.99',
            'commission_percentage' => 'nullable|numeric|between:0,9999999.99',
            'payment_code' => 'required',
            'model_type' => 'required',
            'quote_id' => 'required',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quoteModel = $this->getQuoteObject(request()->model_type, request()->quote_id);
            if ($quoteModel && $quoteModel->quote_status_id == QuoteStatusEnum::PolicyBooked) {
                $validator->errors()->add('value', 'No further editing is required as the policy has been booked');
            }

            if ($quoteModel && $quoteModel->quote_status_id == QuoteStatusEnum::POLICY_BOOKING_FAILED && ! auth()->user()->can(PermissionsEnum::BOOKING_FAILED_EDIT)) {
                $validator->errors()->add('error', 'Policy Booking Failed! Please contact finance for correction of details');
            }

            $iTIN = Payment::where('insurer_tax_number', request()->insurer_tax_invoice_number)->get();
            $isInsurerTaxNumberExists = Payment::whereNotNull('insurer_tax_number')
                ->where(function ($query) use ($quoteModel) {
                    $query->where('paymentable_id', '!=', request()->quote_id)
                        ->where('paymentable_type', '!=', $quoteModel::class);
                })
                ->where('insurer_tax_number', request()->insurer_tax_invoice_number)
                ->select('insurer_tax_number')->first();

            if ($isInsurerTaxNumberExists) {
                $validator->errors()->add('error', 'Insurer Tax Invoice Number already exists, Please enter a unique value.');
            }

            $isInsurerComTaxNumberExists = Payment::whereNotNull('insurer_commmission_invoice_number')
                ->where(function ($query) use ($quoteModel) {
                    $query->where('paymentable_id', '!=', request()->quote_id)
                        ->where('paymentable_type', '!=', $quoteModel::class);
                })
                ->where('insurer_commmission_invoice_number', request()->insurer_commmission_invoice_number)
                ->select('insurer_commmission_invoice_number')->first();

            if ($isInsurerComTaxNumberExists) {
                $validator->errors()->add('error', 'Insurer Commission Invoice Number already exists, Please enter a unique value.');
            }
        });
    }

    public function messages()
    {
        return [
            'commission_vat_not_applicable.required_without' => 'Commmission (VAT APPLICABLE) OR Commmission (VAT NOT APPLICABLE) is required',
            'commission_vat_not_applicable.between' => 'Commmission (VAT NOT APPLICABLE) must be less than 13 digits',

            'commission_vat_applicable.between' => 'Commmission (VAT APPLICABLE) must be less than 13 digits',

        ];
    }
}
