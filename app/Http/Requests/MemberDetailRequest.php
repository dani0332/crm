<?php

namespace App\Http\Requests;

use App\Enums\quoteTypeCode;
use Illuminate\Foundation\Http\FormRequest;

class MemberDetailRequest extends FormRequest
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

        $rules = [
            'dob' => 'required',
            'nationality_id' => 'nullable',
            'first_name' => 'nullable',
            'relation_code' => 'nullable',
        ];

        if (strtolower(request()->modelType) == strtolower(quoteTypeCode::Health)) {
            $rules['health_quote_request_id'] = 'required';
            $rules['gender'] = 'required';
            $rules['emirate_of_your_visa_id'] = 'nullable';
            $rules['member_category_id'] = 'nullable';
            $rules['salary_band_id'] = 'nullable';
            $rules['modelType'] = '';
        } else {
            $rules['quote_request_id'] = 'required';
        }

        return $rules;
    }
}
