<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonalQuoteRequest extends FormRequest
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
            'email' => 'required',
            'mobile_no' => 'required',
            'dob'   => 'required',
            'nationality_id' => 'required',
            'uae_license_held_for_id' => 'required',
            'bike_company_to_insure' => 'required',
            'asset_value' => 'required|numeric',
            'year_of_manufacture' => 'required',
            'currently_insured_with_id' => 'required'
        ];
    }
}
