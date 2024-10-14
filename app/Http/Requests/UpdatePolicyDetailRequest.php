<?php

namespace App\Http\Requests;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

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
            $quote_policy_number = trim(request()->quote_policy_number);
            if (! preg_match($pattern, $quote_policy_number)) {
                $validator->errors()->add('value', 'Invalid format for policy number');
            }

            $modelType = ucwords(ucfirst(request()->modelType));
            $model = $this->getModelObject(request()->modelType);
            $quoteTypeId = collect(QuoteTypeId::getOptions())->search($modelType);
            
            if ($quoteModel->parent_duplicate_quote_id == null) {
                // Check if a policy with the same number and expiry date already exists, excluding the current quote
                $isExists = $model::where('policy_number', $quote_policy_number)
                    ->where('policy_expiry_date', request()->quote_policy_expiry_date)
                    ->where('code', '!=', $quoteModel->code)
                    ->whereNotIn('id', function ($query) use ($model, $quote_policy_number) {
                        $query->select('id')
                            ->from((new $model)->getTable())
                            ->where('policy_number', $quote_policy_number)
                            ->where('policy_expiry_date', request()->quote_policy_expiry_date)
                            ->whereNotNull('parent_duplicate_quote_id');
                    });
                // Apply additional filters based on quote type
                if (checkPersonalQuotes($modelType) || $quoteTypeId == QuoteTypeId::GroupMedical) {
                    // Filter by quote type ID for personal quotes or group medical quotes
                    $isExists->where('quote_type_id', $quoteTypeId);
                }

                if ($quoteTypeId == QuoteTypeId::Business) {
                    // Further filter by business type of insurance ID for group medical quotes
                    if ($quoteModel->business_type_of_insurance_id ==  BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL){
                        $isExists->where('business_type_of_insurance_id', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL);
                    } else {
                        $isExists->where('business_type_of_insurance_id', '!=', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL);
                    }
                }

                if ($isExists->exists()) {
                    // Add an error to the validator if a matching policy is found
                    $validator->errors()->add('quote_policy_number', 'Policy number already exists for this line of business with the same expiry date.');
                }
            }

            $quote = $this->getQuoteObject(request()->modelType, request()->quote_id);
            if ($quote && $quote->quote_status_id == QuoteStatusEnum::POLICY_BOOKING_FAILED && ! auth()->user()->can(PermissionsEnum::BOOKING_FAILED_EDIT)) {
                $validator->errors()->add('error', 'Policy Booking Failed! Please contact finance for correction of details');
            }

            // Check if there are any errors and throw a validation exception if there are
            if ($validator->errors()->isNotEmpty()) {
                throw new ValidationException($validator);
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
