<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KycIndividualDocRequest extends FormRequest
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
            'dob' => 'required',
            'nationality_id' => 'required',
            'country_of_residence' => 'required',
            'place_of_birth' => 'required',
            'resident_status' => 'required',
            'residential_address' => 'required',
            'mobile_number' => 'required',
            'email' => 'required',
            'customer_tenure' => 'required',
            'id_type' => 'required',
            'id_number' => 'required',
            'id_issue_date' => 'required',
            'id_expiry_date' => 'required',
            'mode_of_contact' => 'required',
            'mode_of_delivery' => 'required',
            'income_source' => 'required',
            'company_name' => 'required',
            'professional_title' => 'required_if:income_source,employed',
            'employment_sector' => 'required_if:income_source,employed',
            'trade_license' => 'required_if:income_source,business',
            'company_position' => 'required_if:income_source,business',
            'pep' => 'sometimes',
            'financial_sanctions' => 'sometimes',
            'dual_nationality' => 'sometimes',
            'transaction_pattern' => 'sometimes',
            'premium_tenure' => 'sometimes',
            'in_sanction_list' => 'sometimes',
            'deal_sanction_list' => 'sometimes',
            'is_operation_high_risk' => 'sometimes',
            'is_partner' => 'sometimes',
        ];
    }
}
