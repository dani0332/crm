<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\InsuranceProvidersEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdditionalVehicleDriverDetailsRequest extends FormRequest
{
    /**
     * RTA Transaction Type constants
     */
    private const RTA_NEW_VEHICLE_REGISTRATION = 'RTT01';
    private const RTA_CHANGE_VEHICLE_OWNERSHIP = 'RTT03';
    private const RTA_VEHICLE_RENEWAL = 'RTT04';
    private const RTA_UPDATE_REGISTRATION = 'RTT07';
    private const RTA_VEHICLE_RENEWAL_WITH_CHANGE_NUMBER = 'RTT10';
    
    /**
     * Policy and certificate date constants
     */
    private const POLICY_EFFECTIVE_DATE_MAX_DAYS = 30;
    private const POLICY_DURATION_MONTHS = 13;
    
    /**
     * Insurance provider constants for determining renewal type
     */
    private const GIG_PROVIDER_CODE = 'AXA'; // GIG uses AXA code

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'quote_type_id' => 'required|integer',
            'quote_uuid' => 'required|string',
            'insurance_provider_code' => 'required|string',
            'rta_transaction_type' => 'required',
            'traffic_code_number' => 'required',
            'engine_number' => 'required',
            'chassis_number' => 'required',
            'vehicle_color' => 'required',
            'bank_loan' => 'required',
            'first_registration_date' => 'required|date',
            'is_insured_and_driver_same' => 'required|integer',
            'driver_gender' => 'required|string|in:male,female',
            'driver_license_number' => 'required|string|max:255',
        ];

        // Add conditional rules based on RTA transaction type
        $rules = array_merge($rules, $this->getRtaSpecificRules());

        if ($this->insurance_provider_code == InsuranceProvidersEnum::AXA) {
            $rules['plate_color'] = 'required|string';
            $rules['license_expiry_date'] = 'required|date|after:license_issue_date';
        }

        if ($this->insurance_provider_code == InsuranceProvidersEnum::RSA) {
            $rules['driver_first_name'] = 'required|string|max:255|regex:/^[a-zA-Z0-9\s]+$/';
            $rules['driver_last_name'] = 'required|string|max:255|regex:/^[a-zA-Z0-9\s]+$/';
            $rules['driver_dob'] = 'required|date|before:today';
            $rules['uae_driving_experience'] = 'required|numeric|min:0|max:50';
            $rules['home_country_license_issuance'] = 'required_if:is_insured_and_driver_same,0|string|max:255';
            $rules['home_country_driving_experience'] = 'required_if:is_insured_and_driver_same,0|numeric|min:0|max:50';
        }

        return $rules;
    }

    /**
     * Get RTA transaction type specific validation rules
     */
    private function getRtaSpecificRules(): array
    {
        $rules = [];
        $rtaType = $this->rta_transaction_type;

        switch ($rtaType) {
            case self::RTA_NEW_VEHICLE_REGISTRATION:
                $rules = array_merge($rules, $this->getNewVehicleRegistrationRules());
                break;
                
            case self::RTA_VEHICLE_RENEWAL:
                $rules = array_merge($rules, $this->getVehicleRenewalRules());
                break;
                
            case self::RTA_CHANGE_VEHICLE_OWNERSHIP:
                $rules = array_merge($rules, $this->getChangeVehicleOwnershipRules());
                break;
        }

        return $rules;
    }

    /**
     * Get validation rules for New Vehicle Registration (RTT01)
     */
    private function getNewVehicleRegistrationRules(): array
    {
        return [
            'policy_effective_date' => 'required|date',
            'policy_expiry_date' => 'nullable', // Will be auto-calculated
            'certificate_start_date' => 'nullable', // Will be auto-calculated  
            'certificate_end_date' => 'nullable', // Will be auto-calculated
            // Plate code/number not required for new vehicle registration
            'rta_plate_category' => 'nullable', // Optional
        ];
    }

    /**
     * Get validation rules for Vehicle Renewal (RTT04)
     */
    private function getVehicleRenewalRules(): array
    {
        $isGigRenewal = $this->isGigRenewal();
        
        if ($isGigRenewal) {
            return [
                'policy_effective_date' => 'nullable', // Will be auto-calculated from previous policy
                'policy_expiry_date' => 'nullable', // Will be retrieved from eBao
                'certificate_start_date' => 'required|date', // Selectable
                'certificate_end_date' => 'nullable', // Will be auto-calculated
                'plate_code' => 'required',
                'plate_number' => 'required',
                'rta_plate_category' => 'required',
            ];
        } else {
            return [
                'policy_effective_date' => 'required|date',
                'policy_expiry_date' => 'nullable', // Will be equal to certificate end date
                'certificate_start_date' => 'nullable', // Will be auto-calculated
                'certificate_end_date' => 'nullable', // Will be auto-calculated
                'plate_code' => 'required',
                'plate_number' => 'required', 
                'rta_plate_category' => 'required',
            ];
        }
    }

    /**
     * Get validation rules for Change Vehicle Ownership (RTT03)
     */
    private function getChangeVehicleOwnershipRules(): array
    {
        return [
            'policy_effective_date' => 'required|date',
            'policy_expiry_date' => 'nullable', // Will be auto-calculated
            'certificate_start_date' => 'nullable', // Will be auto-calculated
            'certificate_end_date' => 'nullable', // Will be auto-calculated
            'plate_code' => 'required',
            'plate_number' => 'required',
            'rta_plate_category' => 'required',
        ];
    }

    /**
     * Determine if this is a GIG renewal based on previous policy
     */
    private function isGigRenewal(): bool
    {
        // TODO: Implement logic to check if previous policy is from GIG
        // This would typically involve querying the database for the previous policy
        // based on vehicle details or policy holder information
        
        // For now, you can implement this by checking if there's a previous_policy_provider field
        // or by querying the database based on the quote information
        
        return $this->filled('previous_policy_provider') && 
               $this->previous_policy_provider === self::GIG_PROVIDER_CODE;
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_insured_and_driver_same.required' => 'Please specify if the insured and driver are the same person.',
            'is_insured_and_driver_same.in' => 'Please select either Yes or No for insured and driver same.',
            'driver_first_name.required' => 'Driver first name is required.',
            'driver_first_name.regex' => 'Driver first name can only contain letters, numbers, and spaces.',
            'driver_last_name.required' => 'Driver last name is required.',
            'driver_last_name.regex' => 'Driver last name can only contain letters, numbers, and spaces.',
            'driver_dob.required' => 'Driver date of birth is required.',
            'driver_dob.before' => 'Driver date of birth must be before today.',
            'driver_gender.required' => 'Driver gender is required.',
            'driver_gender.in' => 'Please select a valid gender.',
            'driver_license_number.required' => 'Driver license number is required.',
            'driver_license_number.max' => 'Driver license number cannot exceed 255 characters.',
            'license_issue_date.date' => 'Please enter a valid license issue date.',
            'license_expiry_date.date' => 'Please enter a valid license expiry date.',
            'license_expiry_date.after' => 'License expiry date must be after the issue date.',
            'license_expiry_date.required' => 'License expiry date is required.',
            'uae_driving_experience.required' => 'UAE driving experience is required.',
            'uae_driving_experience.numeric' => 'UAE driving experience must be a number.',
            'uae_driving_experience.min' => 'UAE driving experience cannot be less than 0.',
            'uae_driving_experience.max' => 'UAE driving experience cannot exceed 50 years.',
            'home_country_license_issuance.required' => 'Home country license issuance is required.',
            'home_country_driving_experience.required' => 'Home country driving experience is required.',
            'home_country_driving_experience.numeric' => 'Home country driving experience must be a number.',
            'home_country_driving_experience.min' => 'Home country driving experience cannot be less than 0.',
            'home_country_driving_experience.max' => 'Home country driving experience cannot exceed 50 years.',
            
            // RTA Transaction Type specific messages
            'policy_effective_date.required' => 'Policy effective date is required.',
            'policy_effective_date.date' => 'Please enter a valid policy effective date.',
            'rta_transaction_type.required' => 'RTA transaction type is required.',
            
            // Plate information messages
            'plate_code.required' => 'Plate code is required for this transaction type.',
            'plate_number.required' => 'Plate number is required for this transaction type.',
            'rta_plate_category.required' => 'RTA plate category is required for this transaction type.',
            
            // Certificate and policy dates
            'certificate_start_date.required' => 'Certificate start date is required.',
            'certificate_start_date.date' => 'Please enter a valid certificate start date.',
            'policy_expiry_date.date' => 'Please enter a valid policy expiry date.',
            'certificate_end_date.date' => 'Please enter a valid certificate end date.',
            
            // Vehicle information
            'traffic_code_number.required' => 'Traffic code number is required.',
            'engine_number.required' => 'Engine number is required.',
            'chassis_number.required' => 'Chassis number is required.',
            'vehicle_color.required' => 'Vehicle color is required.',
            'bank_loan.required' => 'Bank loan status is required.',
            'first_registration_date.required' => 'First registration date is required.',
            'first_registration_date.date' => 'Please enter a valid first registration date.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'is_insured_and_driver_same' => 'insured and driver same',
            'driver_first_name' => 'driver first name',
            'driver_last_name' => 'driver last name',
            'driver_dob' => 'driver date of birth',
            'driver_gender' => 'driver gender',
            'driver_license_number' => 'driver license number',
            'license_issue_place' => 'license issue place',
            'license_issue_date' => 'license issue date',
            'license_expiry_date' => 'license expiry date',
            'uae_driving_experience' => 'UAE driving experience',
            'home_country_license_issuance' => 'home country license issuance',
            'home_country_driving_experience' => 'home country driving experience',
            'policy_effective_date' => 'policy effective date',
            'policy_expiry_date' => 'policy expiry date',
            'certificate_start_date' => 'certificate start date',
            'certificate_end_date' => 'certificate end date',
            'rta_transaction_type' => 'RTA transaction type',
            'plate_code' => 'plate code',
            'plate_number' => 'plate number',
            'rta_plate_category' => 'RTA plate category',
            'traffic_code_number' => 'traffic code number',
            'engine_number' => 'engine number',
            'chassis_number' => 'chassis number',
            'vehicle_color' => 'vehicle color',
            'bank_loan' => 'bank loan',
            'first_registration_date' => 'first registration date',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateDriverDetails($validator);
            $this->validateRtaTransactionTypeRules($validator);
        });
    }

    /**
     * Validate all RTA transaction type specific rules
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    private function validateRtaTransactionTypeRules($validator): void
    {
        if (!$this->filled('rta_transaction_type')) {
            return;
        }

        $rtaType = $this->rta_transaction_type;

        switch ($rtaType) {
            case self::RTA_NEW_VEHICLE_REGISTRATION:
                $this->validateNewVehicleRegistration($validator);
                break;
                
            case self::RTA_VEHICLE_RENEWAL:
                $this->validateVehicleRenewal($validator);
                break;
                
            case self::RTA_CHANGE_VEHICLE_OWNERSHIP:
                $this->validateChangeVehicleOwnership($validator);
                break;
        }
    }

    /**
     * Validate New Vehicle Registration (RTT01) specific rules
     */
    private function validateNewVehicleRegistration($validator): void
    {
        // Policy Effective Date: Date can be selected, but cannot be more than 30 days from current date
        $this->validatePolicyEffectiveDateWithinLimit($validator);
        
        // Validate that plate code/number should not be provided (they are disabled)
        if ($this->filled('plate_code') || $this->filled('plate_number')) {
            $validator->errors()->add('plate_code', 'Plate code and plate number are not required for new vehicle registration.');
        }
        
        // Auto-calculated fields validation (should not be manually set)
        $this->validateAutoCalculatedFields($validator, ['policy_expiry_date', 'certificate_start_date', 'certificate_end_date']);
    }

    /**
     * Validate Vehicle Renewal (RTT04) specific rules
     */
    private function validateVehicleRenewal($validator): void
    {
        $isGigRenewal = $this->isGigRenewal();
        
        if ($isGigRenewal) {
            // GIG Renewal validation
            $this->validateGigRenewal($validator);
        } else {
            // Non-GIG Renewal validation
            $this->validateNonGigRenewal($validator);
        }
    }

    /**
     * Validate GIG Renewal specific rules
     */
    private function validateGigRenewal($validator): void
    {
        // Policy Effective Date: Should be locked (auto-calculated from previous policy)
        if ($this->filled('policy_effective_date')) {
            $validator->errors()->add('policy_effective_date', 'Policy effective date is automatically calculated for GIG renewals.');
        }
        
        // Certificate Start Date: Can be selected but no backdating and cannot be after policy effective date
        if ($this->filled('certificate_start_date')) {
            $certificateStartDate = \Carbon\Carbon::parse($this->certificate_start_date);
            $currentDate = \Carbon\Carbon::now()->startOfDay();
            
            // No backdating allowed
            if ($certificateStartDate->lt($currentDate)) {
                $validator->errors()->add('certificate_start_date', 'Certificate start date cannot be backdated.');
            }
            
            // Cannot be after policy effective date (if we have it)
            if ($this->filled('policy_effective_date')) {
                $policyEffectiveDate = \Carbon\Carbon::parse($this->policy_effective_date);
                if ($certificateStartDate->gt($policyEffectiveDate)) {
                    $validator->errors()->add('certificate_start_date', 'Certificate start date cannot be after the policy effective date.');
                }
            }
        }
        
        // Auto-calculated fields validation
        $this->validateAutoCalculatedFields($validator, ['policy_expiry_date', 'certificate_end_date']);
    }

    /**
     * Validate Non-GIG Renewal specific rules
     */
    private function validateNonGigRenewal($validator): void
    {
        // Policy Effective Date: Date can be selected, but cannot be more than 30 days from current date
        $this->validatePolicyEffectiveDateWithinLimit($validator);
        
        // Auto-calculated fields validation
        $this->validateAutoCalculatedFields($validator, ['policy_expiry_date', 'certificate_start_date', 'certificate_end_date']);
    }

    /**
     * Validate Change Vehicle Ownership (RTT03) specific rules
     */
    private function validateChangeVehicleOwnership($validator): void
    {
        // Policy Effective Date: Date can be selected, but cannot be more than 30 days from current date
        $this->validatePolicyEffectiveDateWithinLimit($validator);
        
        // Auto-calculated fields validation
        $this->validateAutoCalculatedFields($validator, ['policy_expiry_date', 'certificate_start_date', 'certificate_end_date']);
    }

    /**
     * Validate policy effective date is within 30 days limit
     */
    private function validatePolicyEffectiveDateWithinLimit($validator): void
    {
        if ($this->filled('policy_effective_date')) {
            $policyEffectiveDate = \Carbon\Carbon::parse($this->policy_effective_date);
            $currentDate = \Carbon\Carbon::now();
            $maxAllowedDate = $currentDate->copy()->addDays(self::POLICY_EFFECTIVE_DATE_MAX_DAYS);

            // Check if policy effective date is more than 30 days from current date
            if ($policyEffectiveDate->gt($maxAllowedDate)) {
                $validator->errors()->add(
                    'policy_effective_date', 
                    'Policy effective date cannot be more than ' . self::POLICY_EFFECTIVE_DATE_MAX_DAYS . ' days from today. Maximum allowed date is ' . $maxAllowedDate->format('Y-m-d') . '.'
                );
            }

            // Check if policy effective date is in the past
            if ($policyEffectiveDate->lt($currentDate->startOfDay())) {
                $validator->errors()->add(
                    'policy_effective_date', 
                    'Policy effective date cannot be in the past.'
                );
            }
        }
    }

    /**
     * Validate that auto-calculated fields are not manually provided
     */
    private function validateAutoCalculatedFields($validator, array $fields): void
    {
        foreach ($fields as $field) {
            if ($this->filled($field)) {
                $fieldName = str_replace('_', ' ', $field);
                $validator->errors()->add($field, "The {$fieldName} is automatically calculated and should not be manually provided.");
            }
        }
    }

    /**
     * Additional driver details validation.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    private function validateDriverDetails($validator): void
    {
        // If license issue date is provided, expiry date should be after it
        if ($this->filled('license_issue_date') && $this->filled('license_expiry_date')) {
            $issueDate = \Carbon\Carbon::parse($this->license_issue_date);
            $expiryDate = \Carbon\Carbon::parse($this->license_expiry_date);

            if ($expiryDate->lte($issueDate)) {
                $validator->errors()->add('license_expiry_date', 'License expiry date must be after the issue date.');
            }
        }

        // If driver DOB is provided, validate minimum age (e.g., 18 years)
        if ($this->filled('driver_dob')) {
            $dob = \Carbon\Carbon::parse($this->driver_dob);
            $age = $dob->age;

            if ($age < 18) {
                $validator->errors()->add('driver_dob', 'Driver must be at least 18 years old.');
            }
        }
    }

    /**
     * Get auto-calculated dates based on RTA transaction type and provided dates
     */
    public function getAutoCalculatedDates(): array
    {
        $calculatedDates = [];
        $rtaType = $this->rta_transaction_type;

        if (!$rtaType) {
            return $calculatedDates;
        }

        switch ($rtaType) {
            case self::RTA_NEW_VEHICLE_REGISTRATION:
            case self::RTA_CHANGE_VEHICLE_OWNERSHIP:
                if ($this->filled('policy_effective_date')) {
                    $policyEffectiveDate = \Carbon\Carbon::parse($this->policy_effective_date);
                    $calculatedDates['policy_expiry_date'] = $policyEffectiveDate->copy()->addMonths(self::POLICY_DURATION_MONTHS)->format('Y-m-d');
                    $calculatedDates['certificate_start_date'] = $policyEffectiveDate->format('Y-m-d');
                    $calculatedDates['certificate_end_date'] = $calculatedDates['policy_expiry_date'];
                }
                break;
                
            case self::RTA_VEHICLE_RENEWAL:
                if ($this->isGigRenewal()) {
                    // For GIG renewal, certificate end date is 13 months from certificate start date
                    if ($this->filled('certificate_start_date')) {
                        $certificateStartDate = \Carbon\Carbon::parse($this->certificate_start_date);
                        $calculatedDates['certificate_end_date'] = $certificateStartDate->copy()->addMonths(self::POLICY_DURATION_MONTHS)->format('Y-m-d');
                    }
                    // Policy effective date and expiry date come from previous policy/eBao
                } else {
                    // For non-GIG renewal
                    if ($this->filled('policy_effective_date')) {
                        $policyEffectiveDate = \Carbon\Carbon::parse($this->policy_effective_date);
                        $calculatedDates['certificate_start_date'] = $policyEffectiveDate->format('Y-m-d');
                        $calculatedDates['certificate_end_date'] = $policyEffectiveDate->copy()->addMonths(self::POLICY_DURATION_MONTHS)->format('Y-m-d');
                        $calculatedDates['policy_expiry_date'] = $calculatedDates['certificate_end_date'];
                    }
                }
                break;
        }

        return $calculatedDates;
    }
}
