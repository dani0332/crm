<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadRateCoverageRequest extends FormRequest
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
            'file_name' => 'required|file|mimes:xls,xlsx|max:2048',
        ];

        return $rules;
    }

    /**
     * check for duplicate file name
     */
    public function withValidator($validator)
    {
        //
    }
}
