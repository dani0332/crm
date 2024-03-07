<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CycleQuoteRequest extends FormRequest
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
            'mobile_no' => 'required',
            'cycle_make' => 'required',
            'cycle_model' => 'required',
            'year_of_manufacture_id' => 'required|exists:year_of_manufacture,id',
            'asset_value' => 'required|numeric',
            'accessories' => 'required',
            'has_accident' => 'required|boolean',
            'has_good_condition' => 'required|boolean',
        ];
    }
}
