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
            'email' => 'required|email:rfc,dns|max:150',
            'mobile_no' => 'required|regex:/(0)[0-9]/|not_regex:/[a-z]/|min:7|max:20',
            'contents_aed' => 'sometimes|required',
            'building_aed' => 'sometimes|required|numeric',
            'personal_belongings_aed' => 'sometimes|required',
            'location_area' => 'required',
            'ownership_status_possession_type_id' => 'required',
            'type_of_property_accommodation_type_id' => 'required',
            'type_of_coverage_you_need' => 'required',
            'type_of_owner_occupancy' => 'required_if:ownership_status_possession_type_id,2',
            'claims' => 'required|boolean',
            'addressObj' => 'sometimes',
            // 'ilivein_accommodation_type_id' => 'required|exists:home_accommodation_type,id',
            // 'iam_possesion_type_id' => 'required|exists:home_possession_type,id',
        ];
    }
}
