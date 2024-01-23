<?php

namespace App\Http\Requests;

use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class SendBookPolicyRequest extends FormRequest
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
            'model_type' => 'required',
            'quote_id' => 'required',
            'send_policy_type' => 'required',

        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            //check for quote records if exists
            $quote = $this->getQuoteObject(request()->model_type, request()->quote_id);
            if ($quote) {
                $payment = Payment::where('code', $quote->code)->first();
                if ($payment) {
                    if (empty($payment->insurer_invoice_date)) {
                        $validator->errors()->add('value', 'Insurer Invoice date is required');
                    }
                    if (empty($payment->insurer_tax_number)) {
                        $validator->errors()->add('value', 'Insurer tax invoice number is required');
                    }
                    if (empty($payment->insurer_commmission_invoice_number)) {
                        $validator->errors()->add('value', 'Insurer Commmission Invoice Number is required');
                    }
                    if (empty($payment->commission_vat_not_applicable) && empty($payment->commission_vat_applicable)) {
                        $validator->errors()->add('value', 'Commmission (VAT NOT APPLICABLE) OR Commmission (VAT APPLICABLE) is required');
                    }
                    $paymentSplit = PaymentSplits::where('code', $quote->code)->first();
                    if (!empty($paymentSplit)) {

                        $invoiceDate = Carbon::parse($payment->insurer_invoice_date)->format('Y-m-d');
                        $paymentDueDate = Carbon::parse($paymentSplit->due_date)->format('Y-m-d');

                        if ($paymentDueDate > $invoiceDate) {
                            $validator->errors()->add('value', 'Payment Due date cannot be earlier than Insurer Invoice date');
                        }
                    }
                } else {
                    $validator->errors()->add('value', 'Payment Not found');
                }
            } else {
                $validator->errors()->add('value', 'Quote Not found');
            }
        });
    }
}
