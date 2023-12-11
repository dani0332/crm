<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAMLCustomerDetailRequest extends FormRequest
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
            'insured_first_name' => 'required',
            'insured_last_name' => 'required',
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
        ];
    }
}
