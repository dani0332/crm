<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LifeSyncHealthQuestionnaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quote_uuid' => 'required|string',
            'policy_number' => 'required|string|max:100',
            'provider_code' => 'required|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'quote_uuid.required' => 'Quote UUID is required.',
            'quote_uuid.string' => 'Quote UUID must be a string.',
            'policy_number.required' => 'Policy number is required.',
            'policy_number.string' => 'Policy number must be a string.',
            'policy_number.max' => 'Policy number may not be greater than 100 characters.',
            'provider_code.required' => 'Provider code is required.',
            'provider_code.string' => 'Provider code must be a string.',
            'provider_code.max' => 'Provider code may not be greater than 10 characters.',
        ];
    }
}
