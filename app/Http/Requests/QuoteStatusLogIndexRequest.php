<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class QuoteStatusLogIndexRequest extends FormRequest
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
            'quoteId' => ['required', 'integer', 'min:1'],
            'quoteTypeId' => ['required', 'integer', 'min:1'],
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
            'quoteId.required' => 'The quote ID is required.',
            'quoteId.integer' => 'The quote ID must be an integer.',
            'quoteId.min' => 'The quote ID must be at least 1.',
            'quoteTypeId.required' => 'The quote type ID is required.',
            'quoteTypeId.integer' => 'The quote type ID must be an integer.',
            'quoteTypeId.min' => 'The quote type ID must be at least 1.',
        ];
    }
}
