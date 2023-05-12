<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class CarQuoteRequest extends FormRequest
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
//            'dob' => 'required|date_format:Y-m-d|before:today',
            'dob' => 'required|before:today',
            'email' => 'required|email:rfc,dns',
            'mobile_no' => 'required',
            'nationality_id' => 'required|exists:nationality,id',
            'uae_license_held_for_id' => 'required|exists:uae_license_held_for,id',
            'car_make_id' => 'required',
//            'car_model_id'  => 'required',
            'cylinder'  => 'required',
//            'year_of_manufacture' => 'required|exists:year_of_manufacture,text',
            'vehicle_type_id'  => 'required',
            'seat_capacity'  => 'required',
            'emirate_of_registration_id'  => 'required',
            'car_type_insurance_id'  => 'required',
            'currently_insured_with' => 'required|exists:insurance_provider,id',
//            'claim_history_id'  => 'required',

        ];
    }
}
