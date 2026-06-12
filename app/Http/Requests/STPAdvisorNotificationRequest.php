<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class STPAdvisorNotificationRequest extends FormRequest
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
            'quoteUuid' => 'required|string',
            'quoteTypeId' => 'required|integer',
            'apiFailed' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'quoteUuid.required' => 'Quote uuid is required',
            'quoteUuid.string' => 'Quote uuid must be a string',
            'quoteTypeId.required' => 'Quote type id is required',
            'quoteTypeId.integer' => 'Quote type id must be an integer',
        ];
    }
}
