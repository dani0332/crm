<?php

namespace App\Http\Requests;

use App\Enums\GenericRequestEnum;
use Illuminate\Foundation\Http\FormRequest;

class LifeQuoteRequest extends FormRequest
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
        $numericCondition = 'required|numeric|min:0';

        return [
            'first_name' => 'required|between:1,20',
            'last_name' => 'required|between:1,50',
            'email' => 'required|email:rfc,dns',
            'mobile_no' => 'required|max:20',
            'dob' => 'required|date',
            'sum_insured_value' => 'required',
            'nationality_id' => 'required|exists:nationality,id',
            'sum_insured_currency_id' => 'required|exists:currency_type,id',
            'marital_status_id' => 'required|exists:marital_status,id',
            'purpose_of_insurance_id' => 'required|exists:life_insurance_purpose,id',
            'height' => $numericCondition,
            'weight' => $numericCondition,
            'bmi' => $numericCondition,
            'age' => $numericCondition,
            'number_of_years_id' => 'required|exists:life_number_of_year,id',
            'is_smoker' => 'required',
            'gender' => 'required|string|in:'.GenericRequestEnum::MALE_SINGLE.','.GenericRequestEnum::FEMALE.'',
            'others_info' => 'nullable',
            // Sub-source validation rules
            'sub_source_id' => 'nullable|exists:lookups,id',
            'sub_source_options_id' => 'nullable|exists:lookups,id',
            'primary_ref_id' => 'nullable|string|max:255',
            'partner_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}
