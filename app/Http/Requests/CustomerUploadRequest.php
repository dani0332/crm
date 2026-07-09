<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_name' => ['required', 'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/excel', 'max:2048'],
            'cdb_id' => 'required|exists:business_quote_request,code',
            'myalfred_expiry_date' => ['required', 'date'],
            'invitation_email' => 'boolean',
        ];
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'invitation_email' => filter_var($this->invitation_email, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }

    public function messages(): array
    {
        return [
            'cdb_id.required' => 'The Ref-ID field is required.',
            'cdb_id.exists' => 'Ref-ID : '.$this->cdb_id." doesn't exists in system.",
        ];
    }
}
