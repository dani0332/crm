<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PUAExportValidationRequest extends FormRequest
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
            'captured_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'authorize_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'captured_date.required' => 'The captured date field is required.',
            'captured_date.date' => 'The captured date must be a valid date.',
            'captured_date.before_or_equal' => 'The captured date cannot be in the future.',
            'authorize_date.required' => 'The authorize date field is required.',
            'authorize_date.date' => 'The authorize date must be a valid date.',
            'authorize_date.before_or_equal' => 'The authorize date cannot be in the future.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'captured_date' => 'captured date',
            'authorize_date' => 'authorize date',
        ];
    }
}
