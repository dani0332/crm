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

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Check if at least one search criteria is provided
            if (! $this->filled('email') && ! $this->filled('policy_number')) {
                $validator->errors()->add('search_criteria', 'At least one search criteria (email or policy number) is required.');
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Get the page number with default value.
     */
    public function getPage(): int
    {
        return $this->integer('page', 1);
    }

    /**
     * Get the email if provided.
     */
    public function getEmail(): ?string
    {
        return $this->filled('email') ? $this->string('email')->toString() : null;
    }

    /**
     * Get the policy number if provided.
     */
    public function getPolicyNumber(): ?string
    {
        return $this->filled('policy_number') ? $this->string('policy_number')->toString() : null;
    }

    /**
     * Get the quote type ID if provided.
     */
    public function getQuoteTypeId(): ?int
    {
        return $this->filled('quote_type_id') ? $this->integer('quote_type_id') : null;
    }
}
