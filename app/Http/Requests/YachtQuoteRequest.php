<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class YachtQuoteRequest extends FormRequest
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
            'boat_details' => 'required',
            'engine_details' => 'required',
            'claim_experience' => 'required',
            'asset_value' => 'required|numeric',
            'use' => 'required',
            'operator_experience' => 'required',
        ];
    }
}
