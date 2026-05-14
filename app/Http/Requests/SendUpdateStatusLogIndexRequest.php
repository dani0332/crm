<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendUpdateStatusLogIndexRequest extends FormRequest
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
            'sendUpdateLogId' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sendUpdateLogId.required' => 'The send update log ID is required.',
            'sendUpdateLogId.integer' => 'The send update log ID must be an integer.',
            'sendUpdateLogId.min' => 'The send update log ID must be at least 1.',
        ];
    }
}
