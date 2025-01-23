<?php

namespace App\Http\Requests;

use App\Enums\CollectionTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\Payment;
use App\Services\InsuranceProviderService;
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
        $rules = [
            'plan_id' => 'required',
        ];

        if (strtolower(request()->quoteType) == strtolower(QuoteTypes::HEALTH->value)) {
            $rules['copay_id'] = 'required';
        }

        if (strtolower(request()->quoteType) == strtolower(QuoteTypes::TRAVEL->value)) {
            $rules['selected_plan_id'] = 'sometimes';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            @[$isCreditCardEnabled] = app(InsuranceProviderService::class)->getPaymentConfiguration(request()->quoteType, request()->insurance_provider_id);
            if (! $isCreditCardEnabled) {
                $payment = Payment::where('code', request()->code)->first();
                if ($payment && $payment->isPaymentAuthorized() && $payment->collection_type == CollectionTypeEnum::INSURER) {
                    $validator->errors()->add('authorized', 'Payment is authorised, and this plan cannot be selected. Please ask your manager to cancel the payment to proceed');
                }
            }
        });
    }
}
