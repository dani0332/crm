<?php

namespace App\Http\Requests;

use App\Models\RatesCoveragesUpload;
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
     * Get the validation error messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'file_name.required' => 'The file is required. Please choose a file.',
            'file_name.file' => 'The uploaded item must be a valid file.',
            'file_name.mimes' => 'The file must be an Excel file with .xls or .xlsx extension.',
            'file_name.max' => 'The file size must not exceed 2MB.',
        ];
    }

    /**
     * check for duplicate file name
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (request()->hasFile('file_name') && RatesCoveragesUpload::where('file_name', request()->file('file_name')->getClientOriginalName())->first()) {
                $validator->errors()->add('type', 'This file has already been uploaded. Please upload a new file with a unique file name.');
            }
        });
    }
}
