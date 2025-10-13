<?php

namespace App\Http\Requests;

use App\Enums\GenericRequestEnum;
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
            'notes' => 'nullable|string',
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
            'gender' => 'nullable|string|in:'.GenericRequestEnum::MALE_SINGLE.','.GenericRequestEnum::FEMALE,
            'chassis_number' => 'nullable|string|min:8|max:17|regex:/^[a-zA-Z0-9]+$/',
            // Sub-source validation rules
            'sub_source_id' => 'nullable|exists:lookups,id',
            'sub_source_options_id' => 'nullable|exists:lookups,id',
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

    public function messages(): array
    {
        return [
            'chassis_number' => 'The entered value does not meet the required length of 8 to 17 characters. Please check and confirm',
        ];
    }
}
