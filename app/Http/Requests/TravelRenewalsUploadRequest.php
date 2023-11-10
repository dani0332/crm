<?php

namespace App\Http\Requests;

use App\Models\RenewalsUploadLeads;
use Illuminate\Foundation\Http\FormRequest;

class TravelRenewalsUploadRequest extends FormRequest
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
            'file_name' => 'required|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/excel|max:2048',
        ];
    }

    /**
     * check for duplicate file name
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (request()->hasFile('file_name') && ($existing = RenewalsUploadLeads::where('file_name', request()->file('file_name')->getClientOriginalName())->first())) {
                $validator->errors()->add('type', 'File already been uploaded. Please try again with different file.');
            }
        });
    }
}
