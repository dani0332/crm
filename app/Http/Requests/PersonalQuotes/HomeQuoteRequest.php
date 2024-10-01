<?php

namespace App\Http\Requests\PersonalQuotes;

use Illuminate\Foundation\Http\FormRequest;

class HomeQuoteRequest extends FormRequest
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
            'first_name' => 'required|between:1,20',
            'last_name' => 'required|between:1,50',
            // 'email' => 'required|email:rfc,dns|max:150',
            // 'mobile_no' => 'required|regex:/(0)[0-9]/|not_regex:/[a-z]/|min:7|max:20',
            // 'premium' => 'sometimes',
            // 'policy_number' => 'sometimes',
            // 'has_contents' => 'sometimes',
            // 'has_building' => 'sometimes',
            // 'has_personal_belongings' => 'sometimes',
            // 'contents_aed' => 'sometimes',
            // 'building_aed' => 'sometimes',
            // 'personal_belongings_aed' => 'sometimes',
            // 'ilivein_accommodation_type_id' => 'required|exists:home_accommodation_type,id',
            // 'iam_possesion_type_id' => 'required|exists:home_possession_type,id',
            // 'address' => 'required|max:2000',
        ];
    }
}
