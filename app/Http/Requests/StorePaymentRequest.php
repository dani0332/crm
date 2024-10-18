<?php

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
        $rules = [
            'modelType' => 'required',
            'quote_id' => 'required|numeric',
        ];

        if ($this->input('new_payment_structure') === true) {
            $rules = [
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
                'send_update_id' => 'nullable|integer',
            ];
        }

        return $rules;
    }

    /**
     * validate quote record
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quoteModel = $this->getQuoteObject(request()->modelType, request()->quote_id);
            if (! $quoteModel) {
                $validator->errors()->add('quote', 'Quote Not Exists');
            } else {

                $expectedPaymentCode = $quoteModel->code;
                if (! empty(request()->send_update_id) || ! empty($quoteModel->parent_duplicate_quote_id)) {
                    // Payment follow-up count is now iterative (uuid-(nth+1)) and not dependent on the count of payments in the quote
                    // Count will be iterative for each payment added through the send update or Child lead

                    if (! empty(request()->send_update_id)) {
                        $paymentAlreadyExistsForSU = Payment::where('send_update_log_id', request()->send_update_id)->count();
                        if ($paymentAlreadyExistsForSU > 0) {
                            $validator->errors()->add('payment', 'Payment Already Added');
                        }
                    }

                    if (! empty($quoteModel->parent_duplicate_quote_id)) {
                        // This will check the double tab case and if the lead created through duplicate functionality
                        $paymentAlreadyExists = $quoteModel->payments->count();

                        // This condition check if the payment already exists and the payment is not added through send update
                        if ($paymentAlreadyExists > 0 && empty(request()->send_update_id)) {
                            $validator->errors()->add('payment', 'Payment Already Added');
                        }
                    }

                    $mainLeadCode = implode('-', array_slice(explode('-', $quoteModel->code), 0, 2));
                    $paymentCount = app(PaymentRepository::class)->getPaymentsCountByLeadCode($mainLeadCode);
                    $expectedPaymentCode = ($paymentCount > 0) ? $mainLeadCode.'-'.$paymentCount : $mainLeadCode;
                }

                $paymentAlreadyExistsCount = Payment::where('code', $expectedPaymentCode)->count();
                if ($paymentAlreadyExistsCount > 0) {
                    $validator->errors()->add('payment', 'Payment Already Added');
                }
            }
            // check if the user is authorized to apply discount
            if (request()->input('payment.discount_value') > 0 && auth()->user()->cannot(PermissionsEnum::PAYMENTS_DISCOUNT_ADD)) {
                $validator->errors()->add('value', 'Not Authorized to Add Discount');
            }
            // check if the user is authorized to apply credit approval
            if (request()->input('payment.credit_approval') != '' && auth()->user()->cannot(PermissionsEnum::PAYMENTS_CREDIT_APPROVAL_ADD)) {
                $validator->errors()->add('value', 'Not Authorized to Add Credit Approval');
            }
        });
    }
}
