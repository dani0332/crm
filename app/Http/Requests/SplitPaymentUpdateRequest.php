<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PermissionsEnum;
use App\Models\PaymentSplits;
use Illuminate\Foundation\Http\FormRequest;

class SplitPaymentUpdateRequest extends FormRequest
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
        return [
            'approved_document_model' => 'array',
            'bank_reference_number' => 'nullable|string',
            'collection_amount' => 'nullable|numeric',
            'customer_id' => 'required|integer',
            'declined_custom_reason' => 'nullable|string',
            'declined_reason' => 'nullable|integer',
            'is_approved' => 'required|boolean',
            'is_declined' => 'required|boolean',
            'modelType' => 'required|string',
            'plan_id' => 'required|integer',
            'quote_id' => 'required|integer',
            'splitPaymentId' => 'required|integer',
            'collection_type' => 'required|string',
        ];
    }

    /**
     * validate quote record
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $request = request();
            $user = auth()->user();

            // Get payment split record if needed
            $paymentSplit = null;
            if ($request->is_approved === true && $user->can(PermissionsEnum::INPL_APPROVER)) {
                $paymentSplit = PaymentSplits::find($request->splitPaymentId);
            }

            // Check if payment method is INPL
            if ($paymentSplit && $paymentSplit->payment_method === PaymentMethodsEnum::InsureNowPayLater) {
                return;
            }

            // Check authorization for broker
            if ($request->collection_type === 'broker' && $request->is_approved === true && $user->cannot(PermissionsEnum::PAYMENT_VERIFICATION_COLLECTED_BY_BROKER)) {
                $validator->errors()->add('value', 'You are not authorized to approve this payment');
            }

            // Check authorization for insurer
            if ($request->collection_type === 'insurer' && $request->is_approved === true && $user->cannot(PermissionsEnum::PAYMENT_VERIFICATION_COLLECTED_BY_INSURER)) {
                $validator->errors()->add('value', 'You are not authorized to approve this payment');
            }
        });
    }
}
