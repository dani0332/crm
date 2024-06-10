<?php

namespace App\Http\Requests;

use App\Enums\GenericRequestEnum;
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
            'first_name' => 'required|between:1,20',
            'last_name' => 'required|between:1,50',
            'email' => 'required|email:rfc,dns',
            'mobile_no' => 'required|max:20',
            'pet_type_id' => 'required|exists:lookups,id',
            'breed_of_pet1' => 'required|max:200',
            'pet_age_id' => 'required|exists:lookups,id',
            'is_neutered' => 'nullable',
            'is_microchipped' => 'nullable',
            'microchip_no' => 'required_if:is_microchipped,=,1',
            'is_mixed_breed' => 'nullable',
            'has_injury' => 'nullable',
            'gender' => 'required|string|in:'.GenericRequestEnum::MALE_SINGLE.','.GenericRequestEnum::FEMALE.'',
        ];
    }
}
