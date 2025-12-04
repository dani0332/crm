<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\Payment;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckDocumentUploadAfterPaymentRequest extends FormRequest
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
            'payment_code' => [
                'required',
                'string',
                'exists:payments,code',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $paymentCode = $this->input('payment_code');

            // Check if payment exists and is authorized
            $payment = Payment::where('code', $paymentCode)
                ->where('payment_status_id', PaymentStatusEnum::AUTHORISED)
                ->with('paymentable')
                ->first();

            if (!$payment) {
                $validator->errors()->add('payment_code', 'Payment not found or not authorized');
                return;
            }

            // Check if payment belongs to any quote
            if (!$payment->paymentable) {
                $validator->errors()->add('payment_code', 'Payment does not belong to any quote');
                return;
            }

            $quote = $payment->paymentable;
            
            if ($quote?->quote_type_id != QuoteTypes::CYBER->id()) {
                $validator->errors()->add('payment_code', 'Payment does not belong to a Cyber quote');
                return;
            }

        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'quote_type.required' => 'Quote type is required',
            'quote_type.string' => 'Quote type must be a string',
            'quote_type.in' => 'Quote type must be Cyber',
            'payment_code.required' => 'Payment code is required',
            'payment_code.string' => 'Payment code must be a string',
            'payment_code.exists' => 'Payment code does not exist',
        ];
    }
}

