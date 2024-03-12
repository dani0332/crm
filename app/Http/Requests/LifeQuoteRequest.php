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
        return [
            'first_name' => 'required|between:1,20',
            'last_name' => 'required|between:1,50',
            'email' => 'required|email:rfc,dns',
            'mobile_no' => 'required|max:20',
            'dob' => 'nullable',
            'sum_insured_value' => 'nullable',
            'nationality_id' => 'nullable|exists:nationality,id',
            'sum_insured_currency_id' => 'nullable',
            'marital_status_id' => 'nullable|exists:marital_status,id',
            'purpose_of_insurance_id' => 'nullable|exists:life_insurance_purpose,id',
            'children_id' => 'nullable|exists:life_children,id',
            'premium' => 'nullable|numeric',
            'tenure_of_insurance_id' => 'nullable|exists:life_insurance_tenure,id',
            'number_of_years_id' => 'nullable|exists:life_number_of_year,id',
            'is_smoker' => 'nullable',
            'gender' => 'nullable|string|in:'.GenericRequestEnum::MALE_SINGLE.','.GenericRequestEnum::FEMALE.'',
            'others_info' => 'nullable',
        ];
    }
}
