<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

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
            'first_name' => 'required|between:1,20',
            'last_name' => 'required|between:1,50',
            'email' => 'required|email:rfc,dns',
            'mobile_no' => 'required',
            'dob' => 'required|date_format:Y-m-d|before:today',
            'nationality_id' => 'required|exists:nationality,id',
            'uae_license_held_for_id' => 'required|exists:uae_license_held_for,id',
            'year_of_manufacture' => 'required|exists:year_of_manufacture,text',
            'additional_notes' => 'nullable',
            'back_home_license_held_for_id' => 'nullable',
            'has_ncd_supporting_documents' => 'nullable',
            'claim_history_id' => 'required',
            'insurance_type_id' => 'required',
            'emirate_of_registration_id' => 'required',
            'seat_capacity' => 'required',
            'make_id' => 'required',
            'model_id' => 'required',
            'bike_value_tier' => 'required',
            'currently_insured_with' => 'required',
            'cubic_capacity' => 'required',
            'asset_value' => 'nullable|numeric',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'dob' => isset($this->dob) ? Carbon::parse($this->dob)->format('Y-m-d') : null,
        ]);
    }
}
