<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LogEpEmailStatusesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'RecordType' => 'required|string',
            'MessageID' => 'required|string',
            'Recipient' => 'nullable|string',
            'Email' => 'nullable|string',
            'Description' => 'nullable|string',
            'Details' => 'nullable|string',
            'Subject' => 'nullable|string',
            'Metadata' => 'nullable|array',
            'Metadata.quote_id' => 'nullable|integer',
            'Metadata.quote_type_id' => 'nullable|integer',
            'Metadata.subject' => 'nullable|string',
        ];
    }
}
