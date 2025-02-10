<?php

namespace App\Http\Requests;

use App\Enums\QuoteTypes;
use App\Models\Payment;
use App\Services\BrokerCommissionService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSelectedPlanRequest extends FormRequest
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
        $quoteType = strtolower(request()->quoteType);

        $rules = [
            'plan_id' => 'required',
        ];

        if ($quoteType == strtolower(QuoteTypes::HEALTH->value)) {
            $rules['copay_id'] = 'required';
        }

        if ($quoteType == strtolower(QuoteTypes::TRAVEL->value)) {
            $rules['selected_plan_id'] = 'sometimes';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $quoteType = request()->quoteType;
        $insuranceProviderId = request()->insurance_provider_id;
        $planId = request()->plan_id;
        $code = request()->code;

        $validator->after(function ($validator) use ($quoteType, $insuranceProviderId, $planId, $code) {
            $quoteTypeId = QuoteTypes::getIdFromValue($quoteType);
            $isCreditCardEnabled = app(BrokerCommissionService::class)->isCreditCardEnabled($quoteTypeId, $insuranceProviderId, $planId);
            if (! $isCreditCardEnabled) {
                $payment = Payment::where('code', $code)->first();
                if ($payment && $payment->isPaymentAuthorized() && $payment->isInsurerPayment()) {
                    $validator->errors()->add('authorized', 'Payment is authorised, and this plan cannot be selected. Please ask your manager to cancel the payment to proceed');
                }
            }
        });
    }
}
