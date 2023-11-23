<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KycEntityDocRequest extends FormRequest
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
            'quote_uuid' => 'required',
            'customer_id' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'company_name' => 'required',
            'legal_structure' => 'required',
            'industry_type' => 'required',
            'country_of_corporation' => 'required',
            'registered_address' => 'required',
            'communication_address' => 'required',
            'mobile_number' => 'required',
            'email' => 'required',
            'website' => 'required',
            'id_document_type' => 'required',
            'id_number' => 'required',
            'id_issue_date' => 'required',
            'id_expiry_date' => 'required',
            'place_of_issue' => 'required',
            'issuing_authority' => 'required',
            'manager_name' => 'required',
            'manager_nationality' => 'required',
            'manager_dob' => 'required',
            'manager_position' => 'required',
            'pep' => 'sometimes',
            'financial_sanctions' => 'sometimes',
            'dual_nationality' => 'sometimes',
        ];
    }
}
