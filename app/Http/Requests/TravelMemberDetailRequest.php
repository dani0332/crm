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
            'dob' => 'required',
            'nationality_id' => 'nullable',
            'first_name' => 'nullable',
            'relation_code' => 'nullable',
        ];
    }
}
