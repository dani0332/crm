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
            'health_quote_request_id' => 'required',
            'gender' => 'required',
            'dob' => 'required',
            'nationality_id' => 'nullable',
            'emirate_of_your_visa_id' => 'nullable',
            'member_category_id' => 'nullable',
            'salary_band_id' => 'nullable',
            'first_name' => 'nullable',
            'relation_code' => 'nullable',
            'modelType' => '',
        ];
    }
}
