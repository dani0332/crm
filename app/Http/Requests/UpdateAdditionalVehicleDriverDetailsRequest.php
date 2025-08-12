<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\InsuranceProvidersEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdditionalVehicleDriverDetailsRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'quote_type_id' => 'required|integer',
            'quote_uuid' => 'required|string',
            'insurance_provider_code' => 'required|string',
        ];

        if (request()->is_insured_and_driver_same) {
            if ($this->insurance_provider_code == InsuranceProvidersEnum::AXA) {
                $rules['license_expiry_date'] = 'required|date|after:license_issue_date';
            } elseif ($this->insurance_provider_code == InsuranceProvidersEnum::RSA) {
                $rules['driver_first_name'] = 'required|string';
                $rules['driver_last_name'] = 'required|string';
                $rules['driver_dob'] = 'required|date|before:today';
                $rules['uae_driving_experience'] = 'required';
                $rules['home_country_license_issuance'] = 'required';
                $rules['home_country_driving_experience'] = 'required';
                $rules['is_insured_and_driver_same'] = 'required|integer';
                $rules['driver_gender'] = 'required|string|in:male,female';
                $rules['driver_license_number'] = 'required|string|max:255';
            }
        } else {
            $rules['plate_color'] = 'nullable|string';
            $rules['rta_transaction_type'] = 'required';
            $rules['chassis_number'] = 'required|string|max:17|min:17|regex:/^[a-zA-Z0-9]+$/';
            $rules['engine_number'] = 'required';
            $rules['vehicle_color'] = 'required';
            $rules['bank_loan'] = 'required';
            $rules['first_registration_date'] = 'required|date';
            $rules['policy_effective_date'] = 'required|date';
            $rules['certificate_start_date'] = 'required|date';

            if ($this->insurance_provider_code == InsuranceProvidersEnum::AXA) {
                $rules['plate_color'] = 'required|string';
            } elseif ($this->insurance_provider_code == InsuranceProvidersEnum::RSA) {
                $rules['plate_code'] = 'required';
                $rules['plate_number'] = 'required';
                $rules['bank_name'] = 'required_if:bank_loan,1';
                $rules['policy_expiry_date'] = 'required|date';
                $rules['certificate_end_date'] = 'required|date';
                $rules['annual_mileage_estimate'] = 'required|integer';
                $rules['traffic_code_number'] = 'required|numeric';
                $rules['policy_effective_date'] = 'required|after_or_equal:today';
            }
        }

        return $rules;
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
            // Additional custom validation logic can be added here
            $this->validateDriverDetails($validator);
        });
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
}
