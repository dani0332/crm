<?php

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Models\Payment;
use App\Services\BrokerCommissionService;
use Illuminate\Foundation\Http\FormRequest;

class PlanDetailsRequest extends FormRequest
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
        $quoteType = request()->quoteType;

        $rules = [
            'insurance_provider_id' => 'required|integer',
            'price_with_vat' => 'required',
            'insurer_quote_number' => 'nullable',
        ];

        if ($quoteType == quoteTypeCode::Life) {
            $rules['price_vat_not_applicable'] = 'required|numeric|regex:/^\d{1,7}(\.\d{1,2})?$/';
        } else {
            $rules['price_vat_applicable'] = 'required|numeric|regex:/^\d{1,7}(\.\d{1,2})?$/';
        }

        if ($quoteType == quoteTypeCode::Business) {
            // for business either price_vat_applicable or price_vat_not_applicable is required, and only one field should have value
            $rules['price_vat_applicable'] = 'nullable|required_without:price_vat_not_applicable|numeric|regex:/^\d{1,7}(\.\d{1,2})?$/';
            $rules['price_vat_not_applicable'] = 'nullable|required_without:price_vat_applicable|numeric|regex:/^\d{1,7}(\.\d{1,2})?$/';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $quoteType = request()->quoteType;
        $code = request()->code;
        $insuranceProviderId = request()->insurance_provider_id;

        $validator->after(function ($validator) use ($quoteType, $code, $insuranceProviderId) {
            $repository = getRepositoryObject($quoteType);
            $quoteModel = $repository::where('code', $code)->firstOrFail();
            $businessTypeId = $quoteModel->business_type_of_insurance_id ?? null;
            if ($quoteModel && $quoteModel->quote_status_id == QuoteStatusEnum::PolicyBooked) {
                $validator->errors()->add('value', 'No further editing is required as the policy has been booked');
            }

            if ($quoteModel && $quoteModel->quote_status_id == QuoteStatusEnum::POLICY_BOOKING_FAILED && ! auth()->user()->can(PermissionsEnum::BOOKING_FAILED_EDIT)) {
                $validator->errors()->add('error', 'Policy Booking Failed! Please contact finance for correction of details');
            }

            $quoteTypeId = QuoteTypes::getIdFromValue($quoteType);
            $isCreditCardEnabled = app(BrokerCommissionService::class)->isCreditCardEnabled($quoteTypeId, $insuranceProviderId, $businessTypeId);

            if (! $isCreditCardEnabled) {
                $payment = Payment::where('code', $code)->first();
                if ($payment && $payment->isPaymentAuthorized() && $payment->isInsurerPayment()) {
                    $validator->errors()->add('authorized', 'Payment is authorised, and this plan cannot be selected. Please ask your manager to cancel the payment to proceed');
                }
            }
        });
    }
}
