<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TravelMemberDetailRequest extends FormRequest
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
            'travel_quote_request_id' => 'required',
            'first_name' => 'sometimes|required',
            'nationality_id' => 'nullable',
            'dob' => 'required',
            'relation_code' => 'nullable',
            'quote_request_id' => 'sometimes|required',
            'customer_id' => 'required',
            'uae_resident' => 'nullable',
            'emirates_id_number' => 'nullable',
            'passport' => 'nullable',
            'gender' => 'nullable',
        ];
    }
}
