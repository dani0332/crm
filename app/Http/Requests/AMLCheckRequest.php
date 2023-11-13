<?php

namespace App\Http\Requests;

use App\Enums\CustomerTypeEnum;
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
            $rules = array_merge($rules, [
                'insured_first_name' => 'required|max:200',
                'insured_last_name' => 'required|max:200',
                'nationality_id' => 'required',
                'dob' => 'required',

                'place_of_birth' => 'required',
                'country_of_residence' => 'required',
                'residential_address' => 'required',
                'residential_status' => 'required',
                'id_type' => 'required',
                'id_issuance_date' => 'required',
                'mode_of_contact' => 'required',
                'transaction_value' => 'required',
                'mode_of_delivery' => 'required',
                'employment_sector' => 'required',
                'customer_tenure' => 'required',

            ]);
        }

        if ($this->customer_type == CustomerTypeEnum::Entity) {
            $rules = array_merge($rules, [
                'trade_license_no' => 'required|max:200',
                'company_name' => 'required|max:200',
                'company_address' => 'required',
                'entity_type_code' => 'nullable',
                'industry_type_code' => 'nullable',
                'emirate_of_registration_id' => 'nullable',
                'legal_structure' => 'required',
                'country_of_corporation' => 'required',
                'website' => 'required',
                'entity_id_type' => 'required',
                'entity_id_issuance_date' => 'required',
                'id_expiry_date' => 'required',
                'id_issuance_place' => 'required',
                'id_issuance_authority' => 'required',
            ]);
        }

        return $rules;

    }
}
