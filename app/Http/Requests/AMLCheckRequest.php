<?php

namespace App\Http\Requests;

use App\Enums\CustomerTypeEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class AMLCheckRequest extends FormRequest
{
    /**
     * Request attribute set only by trusted server-side callers (e.g. {@see AMLService::quoteAmlProcessCall}).
     * Must not be derived from user-controlled input; clients cannot set {@see Request::attributes}.
     */
    public const INTERNAL_AUTOMATION_ATTRIBUTE = 'blanka.aml_internal_automation';

    /**
     * Whether this AML check is running from the queue/automation pipeline (not a browser user forging flags).
     */
    public function isTrustedInternalAutomation(): bool
    {
        return (bool) $this->attributes->get(self::INTERNAL_AUTOMATION_ATTRIBUTE, false);
    }

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'screening_id_type' => 'required|string',
            'screening_id_number' => 'required|string',
        ];

        if ($this->customer_type == CustomerTypeEnum::Individual) {
            $rules = array_merge($rules, [
                'nationality_id' => 'required',
                'dob' => 'required',
                'insured_first_name' => 'required|max:200',
                'insured_last_name' => 'required|max:200',
            ]);

            if (in_array($this->quote_type, [QuoteTypes::CAR->value, QuoteTypes::BIKE->value, QuoteTypes::HOME->value])) {
                $rules['get_quote_email_gig'] = 'nullable|email:rfc,dns';
            }
        }

        if ($this->customer_type == CustomerTypeEnum::Entity) {
            $rules = array_merge($rules, [
                'trade_license_no' => 'required|max:200',
                'company_name' => 'required|max:200',
                'company_address' => 'required',
                'entity_type_code' => 'nullable',
                'industry_type_code' => 'nullable',
                'emirate_of_registration_id' => 'nullable',
            ]);
        }

        $rules['customer_type'] = 'required|string';

        if ($this->quote_type == QuoteTypes::CAR->value &&
            ! (in_array($this->insurance_provider_code, [InsuranceProvidersEnum::AXA]) && $this->lead_source == LeadSourceEnum::RENEWAL_UPLOAD)
        ) {
            $rules['chassis_number'] = 'required|string|min:8|max:17|regex:/^[a-zA-Z0-9]+$/';
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->isTrustedInternalAutomation()) {
                return;
            }

            if (! auth()->user()?->can(PermissionsEnum::AMLList)) {
                $validator->errors()->add('error', 'You don\'t have permission to edit this section.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'chassis_number' => 'The entered value does not meet the required length of 8 to 17 characters. Please check and confirm',
            'get_quote_email_gig' => 'Email in GIG portal must be a valid email address',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        LoggerService::warning('AML Validation Error Summary', extra: [
            'total_errors' => count($errors),
            'validation_errors' => $errors,
            'customer_type' => $this->input('customer_type'),
            'quote_type' => $this->input('quote_type'),
        ]);

        parent::failedValidation($validator);
    }
}
