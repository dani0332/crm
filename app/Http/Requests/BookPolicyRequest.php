<?php

namespace App\Http\Requests;

use App\Models\Payment;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

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
            'booking_date' => 'required',
            'insurer_tax_invoice_number' => 'required|max:22',
            'insurer_commmission_invoice_number' => 'required|max:22|different:insurer_tax_invoice_number',
            'discount' => 'nullable',
            'transaction_payment_status' => 'nullable',
            'commission_percentage' => 'nullable',
            'broker_invoice_number' => 'nullable',
            'commission_vat_not_applicable' => 'required_without:commission_vat_applicable|nullable|numeric|between:0,9999999.99',
            'commission_vat_applicable' => 'nullable|numeric|between:0,9999999.99',
            'total_commission' => 'nullable',
            'invoice_description' => 'required|max:60',
            'vat_on_commission' => 'nullable',
            'commission_percentage' => 'nullable',
            'payment_code' => 'required',
            'model_type' => 'required',
            'quote_id' => 'required',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $iTIN = Payment::where('insurer_tax_number', request()->insurer_tax_invoice_number)->get();

            if (! empty($iTIN[0]['paymentable_id'])) {
                if ($iTIN[0]['paymentable_id'] != request()->quote_id) {
                    $validator->errors()->add('error', 'Insurer Tax Invoice Number already exists,Please enter a unique value');
                }
            }

            $iCIN = Payment::where([['insurer_commmission_invoice_number', request()->insurer_commmission_invoice_number]])->get();

            if (! empty($iCIN[0]['paymentable_id'])) {
                if ($iCIN[0]['paymentable_id'] != request()->quote_id) {
                    $validator->errors()->add('error', 'Insurer Commmission Invoice Number already exists,Please enter a unique value');
                }
            }

            // Check invoice_date and invoice_date can not be earlier than Start date of policy
            $quote = $this->getQuoteObject(request()->model_type, request()->quote_id);
            $policyStartDate = Carbon::parse($quote->policy_start_date);
            $invoiceDate = Carbon::parse(request()->invoice_date);
            if ($policyStartDate->gt($invoiceDate)) {
                $validator->errors()->add('error', 'Insurer Invoice Date can not be earlier than Policy Start Date.');
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
