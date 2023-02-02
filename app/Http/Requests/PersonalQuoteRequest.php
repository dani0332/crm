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
            'first_name' => 'required|max:1',
            'last_name' => 'required',
            'email' => 'required',
            'mobile_no' => 'required',
            'dob'   => 'required',
            'nationality_id' => 'required',
            'uae_license_held_for_id' => 'required',
            'no_of_items' => 'required|int',
            'value' => 'required|numeric',
            'year_of_manufacture_id' => 'required',
            'insurance_provider_id' => 'required'
        ];
    }
}
