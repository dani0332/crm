<?php

namespace App\Http\Requests\Bor;

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
            'lead_id' => ['required', 'integer'],
            'lob' => ['required', 'string'],
            'insurance_provider_id' => 'nullable|integer',
        ];

        // Get LOB and customer type from the request
        $lob = $this->input('lob', 'car');
        $customerType = $this->input('customer_type');
        
        // Customer type specific validation
        if ($customerType === 'Individual') {
            $rules['insurer_name'] = ['required', 'string', 'max:255'];
            $rules['company_name'] = ['nullable'];
        } elseif ($customerType === 'Entity') {
            $rules['company_name'] = ['required', 'string', 'max:255'];
            $rules['insurer_name'] = ['nullable'];
        } else {
            // If customer_type is not set yet, make both optional for now
            $rules['insurer_name'] = ['nullable', 'string', 'max:255'];
            $rules['company_name'] = ['nullable', 'string', 'max:255'];
        }

        // LOB-specific validation rules
        $motorLobs = [QuoteTypes::CAR, QuoteTypes::BIKE];
        $isMotorLob = in_array(strtolower($lob), $motorLobs);

        // Policy fields are only required for Individual insurers, not Entity insurers
        if ($customerType === 'Individual') {
            // Motor LOB specific fields for Individual insurers
            if ($isMotorLob) {
                $rules['policy_number'] = ['required', 'string', 'max:255'];
                $rules['policy_expiry'] = ['required', 'date', 'after:today'];
                
                // Chassis number validation for Sukoon insurance (OIC code)
                $insuranceProviderId = $this->input('insurance_provider_id');
                if ($insuranceProviderId !== null && $this->isSukoonInsurance($insuranceProviderId)) {
                    $rules['chassis_number'] = ['required', 'string', 'max:255'];
                } else {
                    $rules['insurance_provider_id'] = ['integer', 'exists:insurance_provider,id'];
                    $rules['chassis_number'] = ['nullable', 'string', 'max:255'];
                }
            } else {
                // Non-motor LOBs for Individual insurers - these fields are optional
                $rules['policy_number'] = ['nullable', 'string', 'max:255'];
                $rules['policy_expiry'] = ['nullable', 'date', 'after:today'];
                $rules['chassis_number'] = ['nullable', 'string', 'max:255'];
            }
        } else {
            // Entity insurers don't need policy fields
            $rules['policy_number'] = ['nullable', 'string', 'max:255'];
            $rules['policy_expiry'] = ['nullable', 'date', 'after:today'];
            $rules['chassis_number'] = ['nullable', 'string', 'max:255'];
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
            'lead_id.required' => 'Lead ID is required.',
            'insurer_name.required' => 'Insurer name is required for individual insurers.',
            'company_name.required' => 'Company name is required for entity insurers.',
            'insurance_provider_id.exists' => 'The selected insurance provider does not exist.',
            'policy_number.required' => 'Policy number is required for this type of insurance.',
            'policy_expiry.required' => 'Policy expiry date is required for this type of insurance.',
            'policy_expiry.after' => 'Policy expiry date must be in the future.',
            'chassis_number.required' => 'Chassis number is required for Sukoon insurance.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'customer_type' => 'customer type',
            'lead_id' => 'lead',
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
            if ($customerType === 'Entity' && empty($this->input('company_name'))) {
                $validator->errors()->add('company_name', 'Company name is required for entity insurers.');
            }
            
            if ($customerType === 'Individual' && empty($this->input('insurer_name'))) {
                $validator->errors()->add('insurer_name', 'Insurer name is required for individual insurers.');
            }

            // LOB-specific business rules - only for Individual insurers
            if ($customerType === 'Individual') {
                $motorLobs = ['car', 'bike'];
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
        
        if (!$provider) {
            return false;
        }

        // Check if provider code is OIC or name contains Sukoon
        return strtolower($provider->code) === 'oic' || 
               stripos($provider->text, 'sukoon') !== false;
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation()
    {
        // Normalize LOB value
        if ($this->has('lob')) {
            $this->merge([
                'lob' => strtolower($this->input('lob'))
            ]);
        }

        // Ensure numeric fields are properly formatted
        if ($this->has('insurance_provider_id')) {
            $this->merge([
                'insurance_provider_id' => (int) $this->input('insurance_provider_id')
            ]);
        }

        if ($this->has('lead_id')) {
            $this->merge([
                'lead_id' => (int) $this->input('lead_id')
            ]);
        }
    }
}
