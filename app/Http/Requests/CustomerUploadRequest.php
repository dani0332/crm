<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerUploadRequest extends FormRequest
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
            'file_name' => 'required|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/excel|max:2048',
            'cdb_id' => 'required|exists:business_quote_request,code',
            'myalfred_expiry_date' => 'required',
            'inviatation_email' => 'boolean',
        ];
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'inviatation_email' => filter_var($this->inviatation_email, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }

    /**
     * Get the validation rule messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'cdb_id.required' => 'The Ref-ID field is required.',
            'cdb_id.exists' => 'Ref-ID : '.$this->cdb_id." doesn't exists in system.",
        ];
    }
}
