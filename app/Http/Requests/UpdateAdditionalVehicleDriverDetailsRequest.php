<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\LeadSourceEnum;
use App\Models\CarQuote;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdditionalVehicleDriverDetailsRequest extends FormRequest
{
    private const RTA_NEW_VEHICLE_REGISTRATION = 'RTT01';
    private const RTA_CHANGE_VEHICLE_OWNERSHIP = 'RTT03';
    private const RTA_VEHICLE_RENEWAL = 'RTT04';
    private const POLICY_EFFECTIVE_DATE_MAX_DAYS = 30;
    private const POLICY_DURATION_MONTHS = 13;
    private const GIG_PROVIDER_CODE = InsuranceProvidersEnum::AXA;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'quote_type_id' => 'required|integer',
            'quote_uuid' => 'required|string',
            'insurance_provider_code' => 'required|string',
        ];

        if (isset($this->additional_vehicle_transaction_details) && $this->additional_vehicle_transaction_details == true) {
            $rules['rta_transaction_type'] = 'required';
            $rules['plate_color'] = 'required|string';
            $rules['traffic_code_number'] = 'required';
            $rules['engine_number'] = 'required';
            $rules['chassis_number'] = 'required';
            $rules['vehicle_color'] = 'required';
            $rules['bank_loan'] = 'required';
            $rules['first_registration_date'] = 'required|date';
            if ($this->insurance_provider_code === InsuranceProvidersEnum::RSA) {
                $rules['policy_effective_date'] = 'required|after_or_equal:today';
            }
        } else {
            $rules['is_insured_and_driver_same'] = 'required|integer';
            $rules['driver_first_name'] = 'nullable|string|max:255|regex:/^[a-zA-Z0-9\s]+$/';
            $rules['driver_last_name'] = 'nullable|string|max:255|regex:/^[a-zA-Z0-9\s]+$/';
            $rules['driver_dob'] = 'required|date|before:today';
            $rules['driver_gender'] = 'required|string|in:male,female';
            $rules['driver_license_number'] = 'required|string|max:255';
            $rules['uae_driving_experience'] = 'required|numeric|min:0|max:50';
            $rules['home_country_license_issuance'] = 'required_if:is_insured_and_driver_same,0|string|max:255';
            $rules['home_country_driving_experience'] = 'required_if:is_insured_and_driver_same,0|numeric|min:0|max:50';
            $rules['license_expiry_date'] = 'required|date|after:license_issue_date';
        }

        if ($this->insurance_provider_code === InsuranceProvidersEnum::AXA) {
            $rules = array_merge($rules, $this->getRtaSpecificRules());
        }

        return $rules;
    }

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

    private function getNewVehicleRegistrationRules(): array
    {
        return [
            'policy_effective_date' => 'required|date',
            'policy_expiry_date' => 'nullable',
            'certificate_start_date' => 'nullable',
            'certificate_end_date' => 'nullable',
            'rta_plate_category' => 'nullable',
        ];
    }

    private function getVehicleRenewalRules(): array
    {
        $isGigRenewal = $this->isGigRenewal();

        if ($isGigRenewal['parent_quote_id'] === null) {
            return [];
        }

        if ($isGigRenewal['status']) {
            return [
                'policy_effective_date' => 'nullable',
                'policy_expiry_date' => 'nullable',
                'certificate_start_date' => 'required|date',
                'certificate_end_date' => 'nullable',
                'plate_code' => 'required',
                'plate_number' => 'required',
                'rta_plate_category' => 'required',
            ];
        } else {
            return [
                'policy_effective_date' => 'required|date',
                'policy_expiry_date' => 'nullable',
                'certificate_start_date' => 'nullable',
                'certificate_end_date' => 'nullable',
                'plate_code' => 'required',
                'plate_number' => 'required',
                'rta_plate_category' => 'required',
            ];
        }
    }

    private function getChangeVehicleOwnershipRules(): array
    {
        return [
            'policy_effective_date' => 'required|date',
            'policy_expiry_date' => 'nullable',
            'certificate_start_date' => 'nullable',
            'certificate_end_date' => 'nullable',
            'plate_code' => 'required',
            'plate_number' => 'required',
            'rta_plate_category' => 'required',
        ];
    }

    private function isGigRenewal()
    {
        $quoteDetails = CarQuote::where([
            'source' => LeadSourceEnum::RENEWAL_UPLOAD,
            'uuid' => $this->quote_uuid,
        ])->first();

        if (! $quoteDetails) {
            return ['status' => false, 'parent_quote_id' => null, 'isGigRenewal' => false];
        }

        $parentQuote = CarQuote::where([
            'code' => $quoteDetails->parent_duplicate_quote_id,
        ])->first();

        $parentQuoteInsuranceProviderCode = $parentQuote->plan->insurance_provider->code;

        if ($parentQuoteInsuranceProviderCode === self::GIG_PROVIDER_CODE) {
            return ['status' => true, 'parent_quote_id' => $quoteDetails->parent_duplicate_quote_id, 'isGigRenewal' => true];
        }

        return ['status' => false, 'parent_quote_id' => $quoteDetails->parent_duplicate_quote_id, 'isGigRenewal' => false];
    }

    public function messages(): array
    {
        return [
            'is_insured_and_driver_same.required' => 'Please specify if the insured and driver are the same person.',
            'is_insured_and_driver_same.in' => 'Please select either Yes or No for insured and driver same.',
            'driver_first_name.regex' => 'Driver first name can only contain letters, numbers, and spaces.',
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateDriverDetails($validator);
            $this->validateRtaTransactionTypeRules($validator);
        });
    }

    private function validateRtaTransactionTypeRules($validator): void
    {
        if (! $this->filled('rta_transaction_type')) {
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

    private function validateNewVehicleRegistration($validator): void
    {
        $this->validatePolicyEffectiveDateWithinLimit($validator);

        if ($this->filled('plate_code') || $this->filled('plate_number')) {
            $validator->errors()->add('plate_code', 'Plate code and plate number are not required for new vehicle registration.');
        }
    }

    private function validateVehicleRenewal($validator): void
    {
        $isGigRenewal = $this->isGigRenewal();

        if ($isGigRenewal['parent_quote_id'] === null) {
            $validator->errors()->add('rta_transaction_type', 'Vehicle renewal requires parent quote id to proceed.');

            return;
        }

        if ($isGigRenewal['status']) {
            $this->validateGigRenewal($validator);
        } else {
            $this->validateNonGigRenewal($validator);
        }
    }

    private function validateGigRenewal($validator): void
    {
        if ($this->filled('policy_effective_date')) {
            $validator->errors()->add('policy_effective_date', 'Policy effective date is automatically calculated for GIG renewals.');
        }

        if ($this->filled('certificate_start_date')) {
            $certificateStartDate = \Carbon\Carbon::parse($this->certificate_start_date);
            $currentDate = \Carbon\Carbon::now()->startOfDay();

            if ($certificateStartDate->lt($currentDate)) {
                $validator->errors()->add('certificate_start_date', 'Certificate start date cannot be backdated.');
            }

            if ($this->filled('policy_effective_date')) {
                $policyEffectiveDate = \Carbon\Carbon::parse($this->policy_effective_date);
                if ($certificateStartDate->gt($policyEffectiveDate)) {
                    $validator->errors()->add('certificate_start_date', 'Certificate start date cannot be after the policy effective date.');
                }
            }
        }

    }

    private function validateNonGigRenewal($validator): void
    {
        $this->validatePolicyEffectiveDateWithinLimit($validator);
    }

    private function validateChangeVehicleOwnership($validator): void
    {
        $this->validatePolicyEffectiveDateWithinLimit($validator);
    }

    private function validatePolicyEffectiveDateWithinLimit($validator): void
    {
        if ($this->filled('policy_effective_date')) {
            $policyEffectiveDate = \Carbon\Carbon::parse($this->policy_effective_date);
            $currentDate = \Carbon\Carbon::now();
            $maxAllowedDate = $currentDate->copy()->addDays(self::POLICY_EFFECTIVE_DATE_MAX_DAYS);

            if ($policyEffectiveDate->gt($maxAllowedDate)) {
                $validator->errors()->add(
                    'policy_effective_date',
                    'Policy effective date cannot be more than '.self::POLICY_EFFECTIVE_DATE_MAX_DAYS.' days from today. Maximum allowed date is '.$maxAllowedDate->format('Y-m-d').'.'
                );
            }

            if ($policyEffectiveDate->lt($currentDate->startOfDay())) {
                $validator->errors()->add(
                    'policy_effective_date',
                    'Policy effective date cannot be in the past.'
                );
            }
        }
    }

    private function validateDriverDetails($validator): void
    {
        if ($this->filled('is_insured_and_driver_same')) {
            $isInsuredAndDriverSame = $this->is_insured_and_driver_same;

            if ($isInsuredAndDriverSame == 0 || $isInsuredAndDriverSame === '0') {
                if (! $this->filled('driver_first_name')) {
                    $validator->errors()->add('driver_first_name', 'Driver first name is required when insured and driver are not the same.');
                }

                if (! $this->filled('driver_last_name')) {
                    $validator->errors()->add('driver_last_name', 'Driver last name is required when insured and driver are not the same.');
                }
            }
        }

        if ($this->filled('license_issue_date') && $this->filled('license_expiry_date')) {
            $issueDate = \Carbon\Carbon::parse($this->license_issue_date);
            $expiryDate = \Carbon\Carbon::parse($this->license_expiry_date);

            if ($expiryDate->lte($issueDate)) {
                $validator->errors()->add('license_expiry_date', 'License expiry date must be after the issue date.');
            }
        }

        if ($this->filled('driver_dob')) {
            $dob = \Carbon\Carbon::parse($this->driver_dob);
            $age = $dob->age;

            if ($age < 18) {
                $validator->errors()->add('driver_dob', 'Driver must be at least 18 years old.');
            }
        }
    }

    public function getAutoCalculatedDates(): array
    {
        $calculatedDates = [];
        $rtaType = $this->rta_transaction_type;

        if (! $rtaType) {
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
                $gigRenewalData = $this->isGigRenewal();
                if ($gigRenewalData['status']) {
                    if ($this->filled('certificate_start_date')) {
                        $certificateStartDate = \Carbon\Carbon::parse($this->certificate_start_date);
                        $calculatedDates['certificate_end_date'] = $certificateStartDate->copy()->addMonths(self::POLICY_DURATION_MONTHS)->format('Y-m-d');
                    }
                } else {
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
