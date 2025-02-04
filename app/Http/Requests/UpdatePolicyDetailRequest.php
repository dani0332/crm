<?php

namespace App\Http\Requests;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
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
                'quote_policy_start_date' => 'required|date',
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

            $lockStatusOfPolicyIssuanceSteps = (new PolicyIssuanceService)->getPolicyIssuanceStepsStatus($quoteModel, request()->modelType);
            if ($lockStatusOfPolicyIssuanceSteps['isPolicyAutomationEnabled'] && $lockStatusOfPolicyIssuanceSteps['isEditPolicyDetailsDisabled']) {
                $validator->errors()->add('value', 'Policy Booking is scheduled! You are not allowed to edit policy details');
            }

            $this->validatePolicyBooked($validator, $quoteModel);
            $this->validatePolicyNumberFormat($validator);
            $this->validatePolicyNumberExists($validator, $quoteModel);
            $this->validatePolicyBookingFailed($validator, $quoteModel);
            $this->validatePolicyExpiryDate($validator);
            $this->validatePolicyStartDate($validator);

            // Check if there are any errors and throw a validation exception if there are
            if ($validator->errors()->isNotEmpty()) {
                throw new ValidationException($validator);
            }
        });
    }

    private function validatePolicyBooked($validator, $quoteModel)
    {
        if ($quoteModel && $quoteModel->quote_status_id == QuoteStatusEnum::PolicyBooked) {
            $validator->errors()->add('value', 'No further editing is required as the policy has been booked');
        }
    }

    private function validatePolicyNumberFormat($validator)
    {
        $pattern = '/^[\w,\/\\| -]+$/';
        $quote_policy_number = trim(request()->quote_policy_number);
        if (! preg_match($pattern, $quote_policy_number)) {
            $validator->errors()->add('value', 'Invalid format for policy number');
        }
    }

    private function validatePolicyNumberExists($validator, $quoteModel)
    {
        if ($quoteModel->parent_duplicate_quote_id == null) {
            $modelType = ucwords(ucfirst(request()->modelType));
            $model = $this->getModelObject(request()->modelType);
            $quoteTypeId = collect(QuoteTypeId::getOptions())->search($modelType);
            $quote_policy_number = trim(request()->quote_policy_number);

            // Check if a policy with the same number and expiry date already exists, excluding the current quote
            $formattedExpiryDate = Carbon::parse(request()->quote_policy_expiry_date)->format(config('constants.DATE_FORMAT_ONLY'));
            $isExists = $model::where('policy_number', $quote_policy_number)
                ->where('policy_expiry_date', $formattedExpiryDate)
                ->where('code', '!=', $quoteModel->code)
                ->whereNotIn('id', function ($query) use ($model, $quote_policy_number, $formattedExpiryDate) {
                    $query->select('id')
                        ->from((new $model)->getTable())
                        ->where('policy_number', $quote_policy_number)
                        ->where('policy_expiry_date', $formattedExpiryDate)
                        ->whereNotNull('parent_duplicate_quote_id');
                });

            // Apply additional filters based on quote type
            if (checkPersonalQuotes($modelType) || $quoteTypeId == QuoteTypeId::GroupMedical) {
                $isExists->where('quote_type_id', $quoteTypeId);
            }

            if ($quoteTypeId == QuoteTypeId::Business) {
                // Further filter by business type of insurance ID for group medical quotes
                if ($quoteModel->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL) {
                    $isExists->where('business_type_of_insurance_id', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL);
                } else {
                    $isExists->where('business_type_of_insurance_id', '!=', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL);
                }
            }

            $isExists->select('id')->limit(1);
            if ($isExists->exists()) {
                // Add an error to the validator if a matching policy is found
                $validator->errors()->add('quote_policy_number', 'Policy number already exists for this line of business with the same expiry date.');
            }
        }
    }

    private function validatePolicyBookingFailed($validator, $quote)
    {
        if ($quote && $quote->quote_status_id == QuoteStatusEnum::POLICY_BOOKING_FAILED && ! auth()->user()->can(PermissionsEnum::BOOKING_FAILED_EDIT)) {
            $validator->errors()->add('error', 'Policy Booking Failed! Please contact finance for correction of details');
        }
    }

    private function validatePolicyExpiryDate($validator)
    {
        $modelType = ucwords(ucfirst(request()->modelType));
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search($modelType);

        if ($quoteTypeId == QuoteTypeId::Car) {
            $startDate = Carbon::parse(request()->quote_policy_start_date);
            $expiryDate = Carbon::parse(request()->quote_policy_expiry_date);

            // Check if expiry date is within 13 months of the start date
            if ($startDate->diffInMonths($expiryDate) > 13) {
                $validator->errors()->add('quote_policy_expiry_date', 'The expiry date must be within 13 months of the start date.');
            }
        }
    }

    private function validatePolicyStartDate($validator)
    {
        $modelType = ucwords(ucfirst(request()->modelType));
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search($modelType);
        $startDate = Carbon::parse(request()->quote_policy_start_date);

        if ($quoteTypeId !== QuoteTypeId::Travel) {
            $maxStartDate = Carbon::now()->addMonths(3)->endOfDay();

            if ($startDate > $maxStartDate) {
                $validator->errors()->add('quote_policy_start_date', 'Please select a date within the next three months.');
            }
        }
    }

    public function messages()
    {
        return [
            'price_vat_notapplicable.required_without' => 'Price (VAT NOT APPLICABLE) OR Price (VAT APPLICABLE) is required',
            'price_vat_notapplicable.between' => 'Price (VAT NOT APPLICABLE) must be less than 13 digits',
            'amount.between' => 'Price (VAT NOT APPLICABLE) must be less than 13 digits',
            'amount_with_vat.required' => 'Total price is required',
            'quote_policy_expiry_date.after' => 'Please select a date that is after the start date and in the current or future year',
        ];
    }
}
