<?php

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
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
            'modelType' => 'required',
            'quote_id' => 'required|numeric',
            'plan_id' => 'nullable|integer',
            'insurance_provider_id' => 'nullable|integer',
            'payment.total_price' => 'required|numeric|min:0',
            'payment.notes' => 'nullable|string',
            'payment.custom_reason' => 'nullable|string',
            'payment.discount_reason' => 'nullable|string',
            'payment.discount_custom_reason' => 'nullable|string',
            'payment.discount_type' => 'nullable|string',
            'payment.frequency' => 'required|string|in:upfront,monthly,quarterly,semi_annual,split_payments,custom',
            'payment.collection_type' => 'required|string|in:broker,insurer',
            'payment.total_amount' => 'required|numeric|min:0',
            'payment.collection_date' => 'required|date',
            'payment.discount_value' => 'nullable|numeric|min:0',
            'payment.payment_methods' => 'required|string',
            'payment.payment_splits.*.sr_no' => 'required|integer|min:1',
            'payment.payment_splits.*.payment_amount' => 'required|numeric',
            'payment.payment_splits.*.payment_method' => 'required|string',
            'payment.payment_splits.*.due_date' => 'required|date',
        ];

        return $rules;
    }

    /**
     * validate quote record
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $payment = Payment::where('code', request()->paymentCode)->first();

            // check if the user is authorized to apply discount
            if (request()->input('payment.discount_value') > 0 && $payment->discount_value != request()->input('payment.discount_value') && auth()->user()->cannot(PermissionsEnum::PAYMENTS_DISCOUNT_ADD)) {
                $validator->errors()->add('value', 'Not Authorized to Add Discount');
            }
            // check if the user is authorized to apply credit approval
            if (request()->input('payment.credit_approval') != '' && $payment->credit_approval != request()->input('payment.credit_approval') && auth()->user()->cannot(PermissionsEnum::PAYMENTS_CREDIT_APPROVAL_ADD)) {
                $validator->errors()->add('value', 'Not Authorized to Add Credit Approval');
            }

            if (request()->input('isPolicyIssuanceDiscount') && auth()->user()->cannot(PermissionsEnum::PAYMENTS_DISCOUNT_EDIT)) {
                $validator->errors()->add('value', 'Not Authorized to Edit Discount');
            }

        });
    }
}
