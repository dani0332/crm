<?php

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
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
            'dob' => 'sometimes',
            'nationality_id' => 'nullable',
            'first_name' => 'nullable',
            'last_name' => 'nullable',
            'relation_code' => 'nullable',
            'quote_request_id' => 'sometimes|required',
            'customer_id' => 'required',
        ];

        if (strtolower(request()->quote_type) == strtolower(quoteTypeCode::Health)) {
            $rules['quote_request_id'] = 'sometimes|required';
            $rules['gender'] = 'sometimes|required';
            $rules['emirate_of_your_visa_id'] = 'nullable';
            $rules['member_category_id'] = 'nullable';
            $rules['salary_band_id'] = 'nullable';
            $rules['modelType'] = '';
            $rules['first_name'] = 'sometimes|required';
            $rules['pec'] = 'sometimes|required';
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (request()->from_aml_model && ! auth()->user()->can(PermissionsEnum::AMLList)) {
                $validator->errors()->add('error', 'You don\'t have permission to edit this section.');
            }
        });
    }
}
