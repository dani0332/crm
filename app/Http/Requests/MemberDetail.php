<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MemberDetail extends FormRequest
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
            'gender' => 'required',
            'dob' => 'required',
            'member_category' => 'required',
            'salary_band' => 'required',
            'health_quote_request_id' => 'required',
            'modelType' => '',
        ];
    }
}
