<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SicConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function rules()
    {
        return [
            'id' => 'nullable',
            'is_age' => 'nullable',
            'min_age' => 'nullable',
            'max_age' => 'nullable',
            'is_type' => 'nullable',
            'quote_type_id' => 'nullable',
            'is_nationality' => 'nullable',
            'is_member_category' => 'nullable',
            'plan_types' => 'array|nullable',
            'nationalities' => 'array|nullable',
            'member_categories' => 'array |nullable',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['message' => $validator->errors()]), 422);
    }
}
