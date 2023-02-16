<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BikeQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email:rfc,dns',
            'mobile_no' => 'required',
            'dob' => 'required|date_format:Y-m-d|before:today',
            'nationality_id' => 'required|exists:nationality,id',
            'uae_license_held_for_id' => 'required|exists:uae_license_held_for,id',
            'bike_company_to_insure' => 'required',
            'asset_value' => 'required|numeric',
            'year_of_manufacture' => 'required',
            'currently_insured_with_id' => 'required|exists:insurance_provider,id',
        ];
    }
}
