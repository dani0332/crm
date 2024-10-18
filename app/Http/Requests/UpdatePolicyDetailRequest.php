<?php

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePolicyDetailRequest extends FormRequest
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

        if (! empty(request()->quote_policy_issuance_status) && request()->price_with_vat <= 0 && empty(request()->quote_policy_number)) {
            return [
                'quote_policy_issuance_status' => 'nullable',
                'quote_policy_issuance_status_other' => 'nullable',
                'modelType' => 'required',
                'quote_id' => 'required',

            ];
        } else {

            return [

                'quote_policy_number' => 'required|max:75',
                'quote_policy_issuance_date' => 'required',
                'quote_policy_start_date' => 'required',
                'quote_policy_expiry_date' => 'required|date|after:quote_policy_start_date',
                'price_vat_notapplicable' => 'required_without:price_vat_applicable|nullable|numeric|between:0,9999999.99',
                'price_vat_applicable' => 'nullable|numeric|between:0,9999999.99',
                'amount_with_vat' => 'required',
                'vat' => 'nullable',
                'quote_plan_insurer_quote_number' => 'nullable',
                'quote_policy_issuance_status' => 'nullable',
                'quote_policy_issuance_status_other' => 'nullable',
                'modelType' => 'required',
                'quote_id' => 'required',

            ];
        }
    }

    // regex to allow alphanumeric, dash and forward slash only

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quoteModel = $this->getQuoteObject(request()->modelType, request()->quote_id);
            if ($quoteModel && $quoteModel->quote_status_id == QuoteStatusEnum::PolicyBooked) {
                $validator->errors()->add('value', 'No further editing is required as the policy has been booked');
            }
            $pattern = '/^[\w,\/\\| -]+$/';
            $quote_policy_number = request()->quote_policy_number;
            if (! preg_match($pattern, $quote_policy_number)) {
                $validator->errors()->add('value', 'Invalid format for policy number');
            }
            $quote = $this->getQuoteObject(request()->modelType, request()->quote_id);
            if ($quote && $quote->quote_status_id == QuoteStatusEnum::POLICY_BOOKING_FAILED && ! auth()->user()->can(PermissionsEnum::BOOKING_FAILED_EDIT)) {
                $validator->errors()->add('error', 'Policy Booking Failed! Please contact finance for correction of details');
            }
        });
    }

    public function messages()
    {
        return [
            'price_vat_notapplicable.required_without' => 'Price (VAT NOT APPLICABLE) OR Price (VAT APPLICABLE) is required',
            'price_vat_notapplicable.between' => 'Price (VAT NOT APPLICABLE) must be less than 13 digits',
            'amount.between' => 'Price (VAT NOT APPLICABLE) must be less than 13 digits',
            'amount_with_vat.required' => 'Total price is required',

        ];
    }
}
