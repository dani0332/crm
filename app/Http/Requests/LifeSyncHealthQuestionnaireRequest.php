<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LifeSyncHealthQuestionnaireRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quote_uuid' => 'required|string',
            'policy_number' => 'required|string|max:100',
            'provider_code' => 'required|string|max:10',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
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
