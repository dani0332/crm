<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Controllers\V2\CentralController;
use App\Models\PaymentSplits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RetryPrepaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paymentSplitId' => 'required',
            'quoteRequestId' => 'required',
            'quoteType' => 'required',
            'paymentCode' => 'required',
            'srNo' => 'required',
            'sendUpdateId' => 'nullable',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $paymentSplitId = $this->input('paymentSplitId');
            $quoteType = $this->input('quoteType');
            $quoteRequestId = $this->input('quoteRequestId');

            // Validate main lead object exists
            $mainLeadObject = app(CentralController::class)->getQuoteObject($quoteType, $quoteRequestId);
            if (! $mainLeadObject) {
                $validator->errors()->add('quote_request_id', 'Main lead object not found.');
            }

            // Validate payment split exists
            $paymentSplit = PaymentSplits::find($paymentSplitId);
            if (! $paymentSplit) {
                $validator->errors()->add('payment_split_id', 'Payment split not found.');
                return;
            }

            // Validate payment exists
            $payment = $paymentSplit->payment;
            if (! $payment) {
                $validator->errors()->add('payment_split_id', 'Payment not found.');
            }
        });
    }
}
