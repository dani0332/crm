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
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email:rfc,dns',
            'mobile_no' => 'required',
            'type_of_pet1' => 'required',
            'breed_of_pet1' => 'required',
            'age_of_pet1' => 'required',
            'ilivein_accommodation_type_id' => 'required',
            'iam_possesion_type_id' => 'required',
        ];
    }
}
