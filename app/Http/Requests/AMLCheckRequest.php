<?php

namespace App\Http\Requests;

use App\Enums\CustomerTypeEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteTypes;
use Illuminate\Foundation\Http\FormRequest;

class AMLCheckRequest extends FormRequest
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
        $rules = [];
        if ($this->customer_type == CustomerTypeEnum::Individual) {
            $rules = [
                'nationality_id' => 'required',
                'dob' => 'required',
                'insured_first_name' => 'required|max:200',
                'insured_last_name' => 'required|max:200',
            ];

            if (in_array($this->quote_type, [QuoteTypes::CAR->value, QuoteTypes::BIKE->value, QuoteTypes::HOME->value])) {
                $rules['get_quote_email_gig'] = 'nullable|email:rfc,dns';
            }
        }

        if ($this->customer_type == CustomerTypeEnum::Entity) {
            $rules = [
                'trade_license_no' => 'required|max:200',
                'company_name' => 'required|max:200',
                'company_address' => 'required',
                'entity_type_code' => 'nullable',
                'industry_type_code' => 'nullable',
                'emirate_of_registration_id' => 'nullable',
            ];
        }

        $rules['customer_type'] = 'required|string';

        if ($this->quote_type == QuoteTypes::CAR->value) {
            $rules['chassis_number'] = 'required|string|min:8|max:17|regex:/^[a-zA-Z0-9]+$/';
            if (isset($this->insurance_provider_code) && $this->insurance_provider_code == InsuranceProvidersEnum::RSA) {
                $rules['rta_transaction_type'] = 'required';
                $rules['plate_code'] = 'required';
                $rules['plate_number'] = 'required';
                $rules['traffic_code_number'] = 'required';
                $rules['engine_number'] = 'required';
                $rules['rta_plate_category'] = 'nullable';
                $rules['vehicle_color'] = 'required';
                $rules['plate_color'] = 'nullable|string';
                $rules['bank_loan'] = 'required';
                $rules['bank_name'] = 'required_if:bank_loan,1';
                $rules['first_registration_date'] = 'required|date';
                $rules['policy_effective_date'] = 'required|date';
                $rules['policy_expiry_date'] = 'required|date';
                $rules['certificate_start_date'] = 'required|date';
                $rules['certificate_end_date'] = 'required|date';
                $rules['annual_mileage_estimate'] = 'required|integer';
                // Additional Driver Details
                $rules['is_insured_and_driver_same'] = 'required|integer';
                $rules['driver_first_name'] = 'required|string';
                $rules['driver_last_name'] = 'required|string';
                $rules['driver_dob'] = 'required|date';
                $rules['driver_gender'] = 'required|string';
                $rules['driver_license_number'] = 'required|string';
                $rules['license_issue_place'] = 'required';
                $rules['license_issue_date'] = 'required';
                $rules['license_expiry_date'] = 'required';
                $rules['uae_driving_experience'] = 'required|integer';
                $rules['home_country_license_issuance'] = 'required_if:is_insured_and_driver_same,0';
                $rules['home_country_driving_experience'] = 'required|integer';
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'chassis_number' => 'The entered value does not meet the required length of 8 to 17 characters. Please check and confirm',
            'get_quote_email_gig' => 'Email in GIG portal must be a valid email address',
        ];
    }
}
