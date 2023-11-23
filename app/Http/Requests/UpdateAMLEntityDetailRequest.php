<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAMLEntityDetailRequest extends FormRequest
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
        return [
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
        ];
    }
}
