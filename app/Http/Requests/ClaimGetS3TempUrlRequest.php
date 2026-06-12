<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClaimGetS3TempUrlRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'docURL' => 'required|string|max:2048',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'docURL' => 'document URL',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'docURL.required' => 'The document URL is required.',
            'docURL.string' => 'The document URL must be a valid text.',
            'docURL.max' => 'The document URL may not exceed 2048 characters.',
        ];
    }
}
