<?php

namespace App\Http\Requests\Bor;

use App\Enums\CustomerTypeEnum;
use App\Enums\InsuranceProviderEnum;
use App\Enums\QuoteTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BorFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'customer_type' => ['required', 'string', Rule::in(['Individual', 'Entity'])],
            'personal_quote_id' => ['required', 'integer'],
            'lob' => ['required', 'string'],
            'insurance_provider_id' => 'nullable|integer',
        ];

        // Get LOB and customer type from the request
        $lob = $this->input('lob', 'car');
        $customerType = $this->input('customer_type');

        // Customer type specific validation
        if ($customerType === 'Individual') {
            $rules['insurer_name'] = ['required', 'string', 'max:100'];
            $rules['company_name'] = ['nullable', 'max:100', 'regex:/^[a-zA-Z\s]+$/'];
        } elseif ($customerType === 'Entity') {
            $rules['company_name'] = ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'];
            $rules['insurer_name'] = ['nullable'];
        } else {
            // If customer_type is not set yet, make both optional for now
            $rules['insurer_name'] = ['nullable', 'string', 'max:100'];
            $rules['company_name'] = ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'];
        }

        // LOB-specific validation rules
        $motorLobs = [strtolower(QuoteTypes::CAR->value), strtolower(QuoteTypes::BIKE->value)];
        $isMotorLob = in_array(strtolower($lob), $motorLobs);

        // Policy fields are only required for Individual insurers, not Entity insurers
        if ($customerType === 'Individual') {
            // Motor LOB specific fields for Individual insurers
            if ($isMotorLob) {
                $rules['policy_number'] = ['required', 'string', 'max:40', 'regex:/^(?!\s*$).+/'];
                $rules['policy_expiry'] = ['required', 'date', 'after:today'];

                // Chassis number validation for Sukoon insurance (OIC code)
                $insuranceProviderId = $this->input('insurance_provider_id');
                if ($insuranceProviderId !== null && $this->isSukoonInsurance($insuranceProviderId)) {
                    $rules['chassis_number'] = ['required', 'string', 'min:8', 'max:17', 'regex:/^(?!\s*$).+/'];
                } else {
                    $rules['insurance_provider_id'] = ['nullable', 'integer', 'exists:insurance_provider,id'];
                    $rules['chassis_number'] = ['nullable', 'string', 'min:8', 'max:17', 'regex:/^(?!\s*$).+/'];
                }
            } else {
                // Non-motor LOBs for Individual insurers - these fields are optional
                $rules['policy_number'] = ['nullable', 'string', 'max:40', 'regex:/^(?!\s*$).+/'];
                $rules['policy_expiry'] = ['nullable', 'date', 'after:today'];
                $rules['chassis_number'] = ['nullable', 'string', 'min:8', 'max:17', 'regex:/^(?!\s*$).+/'];
            }
        } else {
            // Entity insurers don't need policy fields
            $rules['policy_number'] = ['nullable', 'string', 'max:40', 'regex:/^(?!\s*$).+/'];
            $rules['policy_expiry'] = ['nullable', 'date', 'after:today'];
            $rules['chassis_number'] = ['nullable', 'string', 'min:8', 'max:17', 'regex:/^(?!\s*$).+/'];
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'customer_type.required' => 'Customer type is required.',
            'customer_type.in' => 'Customer type must be either Individual or Entity.',
            'personal_quote_id.required' => 'Lead ID is required.',
            'insurer_name.required' => 'Insurer name is required for individual insurers.',
            'company_name.required' => 'Company name is required for entity insurers.',
            'insurance_provider_id.exists' => 'The selected insurance provider does not exist.',
            'policy_number.required' => 'Policy number is required for this type of insurance.',
            'policy_number.regex' => 'Policy number cannot be empty or contain only spaces.',
            'policy_expiry.required' => 'Policy expiry date is required for this type of insurance.',
            'policy_expiry.after' => 'Policy expiry date must be in the future.',
            'chassis_number.required' => 'Chassis number is required for Sukoon insurance.',
            'company_name.regex' => 'Company name can only contain alphabetic characters and spaces.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'customer_type' => 'customer type',
            'personal_quote_id' => 'lead',
            'insurer_name' => 'insurer name',
            'company_name' => 'company name',

            'insurance_provider_id' => 'insurance provider',
            'policy_number' => 'policy number',
            'policy_expiry' => 'policy expiry date',
            'chassis_number' => 'chassis number',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $customerType = $this->input('customer_type');
            $lob = $this->input('lob', 'car');

            // Additional business rule validations
            if ($customerType === CustomerTypeEnum::Entity && empty($this->input('company_name'))) {
                $validator->errors()->add('company_name', 'Company name is required for entity insurers.');
            }

            if ($customerType === CustomerTypeEnum::Individual && empty($this->input('insurer_name'))) {
                $validator->errors()->add('insurer_name', 'Insurer name is required for individual insurers.');
            }

            // LOB-specific business rules - only for Individual insurers
            if ($customerType === CustomerTypeEnum::Individual) {
                $motorLobs = [strtolower(QuoteTypes::CAR->value), strtolower(QuoteTypes::BIKE->value)];
                if (in_array(strtolower($lob), $motorLobs)) {
                    if (empty($this->input('policy_number'))) {
                        $validator->errors()->add('policy_number', 'Policy number is required for motor insurance.');
                    }

                    if (empty($this->input('policy_expiry'))) {
                        $validator->errors()->add('policy_expiry', 'Policy expiry date is required for motor insurance.');
                    }
                }
            }
        });
    }

    /**
     * Check if the selected insurance provider is Sukoon (OIC)
     */
    private function isSukoonInsurance($insuranceProviderId): bool
    {
        // Check if the provider is Sukoon/OIC
        $provider = \App\Models\InsuranceProvider::find($insuranceProviderId);

        if (! $provider) {
            return false;
        }

        // Check if provider code is OIC or name contains Sukoon
        return strtolower($provider->code) === strtolower(InsuranceProviderEnum::OIC->value) ||
               stripos($provider->text, strtolower(InsuranceProviderEnum::getTextByCode(InsuranceProviderEnum::OIC))) !== false;
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation()
    {
        // Normalize LOB value
        if ($this->has('lob')) {
            $this->merge([
                'lob' => strtolower($this->input('lob')),
            ]);
        }

        // Ensure numeric fields are properly formatted
        if ($this->has('insurance_provider_id')) {
            $this->merge([
                'insurance_provider_id' => $this->input('insurance_provider_id'),
            ]);
        }

        if ($this->has('personal_quote_id')) {
            $this->merge([
                'personal_quote_id' => (int) $this->input('personal_quote_id'),
            ]);
        }
    }
}
