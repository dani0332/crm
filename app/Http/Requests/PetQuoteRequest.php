<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PetQuoteRequest extends FormRequest
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
            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',
            'email' => 'required|email:rfc,dns',
            'mobile_no' => 'required',
            'premium' => 'nullable|numeric',
            'policy_number' => 'nullable|max:200',
            'pet_type_id' => 'required|exists:lookups,id',
            'breed_of_pet1' => 'required|max:200',
            'pet_age_id' => 'required|exists:lookups,id',
            'is_neutered' => 'nullable',
            'is_microchipped' => 'nullable',
            'microchip_no' => 'required_if:is_microchipped,=,1',
            'is_mixed_breed' => 'nullable',
            'has_injury' => 'nullable',
            'gender' => 'required|string|in:Male,Female',
            'ilivein_accommodation_type_id' => 'required|exists:home_accommodation_type,id',
            'iam_possesion_type_id' => 'required|exists:home_possession_type,id',
        ];
    }
}
