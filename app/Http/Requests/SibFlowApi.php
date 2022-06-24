<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class SibFlowApi extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function rules()
    {
        return [
            'quoteUID' => 'required|number',
            'quoteTypeID' => 'required|number',
            'QuoteStatus' => 'required|number'
        ];
    }

    public function failedValidation(Validator $validator)
    {
       throw new HttpResponseException(response()->json(['message' => $validator->errors()]), 422);
    }
}
