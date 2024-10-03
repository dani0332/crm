<?php

namespace App\Http\Requests;

use App\Enums\PaymentFrequency;
use App\Enums\PaymentStatusEnum;
use App\Models\PaymentSplits;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

class DeleteSplitPaymentRequest extends FormRequest
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
            'model_type' => 'required|string',
            'quote_id' => 'required|integer',
            'payment_split_id' => 'required|integer',
            'payment_status_id' => 'required|integer',
        ];
    }

    /**
     * validate quote record
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quoteModel = $this->getQuoteObject($this->input('model_type'), $this->input('quote_id'));
            if (! $quoteModel) {
                $validator->errors()->add('quote_id', 'Quote does not exist.');
            }

            $paidStatuses = [
                PaymentStatusEnum::PAID,
                PaymentStatusEnum::PARTIALLY_PAID,
                PaymentStatusEnum::PARTIAL_CAPTURED,
                PaymentStatusEnum::CAPTURED,
                PaymentStatusEnum::AUTHORISED,
                PaymentStatusEnum::REFUNDED,
            ];
            if (in_array($this->input('payment_status_id'), $paidStatuses)) {
                $validator->errors()->add('payment_status_id', 'Payment deletion not allowed.');
            }

            $paymentSplit = PaymentSplits::find($this->input('payment_split_id'));
            if (! $paymentSplit) {
                $validator->errors()->add('payment_split_id', 'Payment split does not exist.');
            } elseif ($paymentSplit->payment->frequency == PaymentFrequency::UPFRONT) {
                $validator->errors()->add('payment_status_id', 'Payment deletion not allowed for upfront payments.');
            }
        });
    }
}
