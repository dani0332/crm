<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendZeroPlanEmailRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'quoteUuid.required' => 'Quote UUID is required',
            'quoteUuid.string' => 'Quote UUID must be a string',
            'quoteTypeId.required' => 'Quote Type ID is required',
            'quoteTypeId.integer' => 'Quote Type ID must be an integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'quoteUuid' => 'Quote UUID',
            'quoteTypeId' => 'Quote Type ID',
        ];
    }
}
