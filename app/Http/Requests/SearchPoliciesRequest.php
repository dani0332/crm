<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SearchPoliciesRequest extends FormRequest
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
            'email' => 'required|email|max:255',
            'quote_type_id' => 'required|integer|exists:quote_type,id',
            'policy_number' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email address must not exceed 255 characters.',
            'policy_number.max' => 'Policy number must not exceed 100 characters.',
            'quote_type_id.integer' => 'Please select a valid line of business.',
            'quote_type_id.exists' => 'The selected line of business is invalid.',
            'page.integer' => 'Page number must be a valid number.',
            'page.min' => 'Page number must be at least 1.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => 'email address',
            'policy_number' => 'policy number',
            'quote_type_id' => 'line of business',
            'page' => 'page number',
        ];
    }
}
