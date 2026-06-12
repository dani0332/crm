<?php

namespace App\Http\Requests;

use App\Enums\QuoteTypes;
use App\Models\Department;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetAdvisorsByQuoteTypeRequest extends FormRequest
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
            'quote_type' => ['required', Rule::enum(QuoteTypes::class)],
            'department_ids' => ['sometimes', 'array'],
            'department_ids.*' => ['integer', Rule::exists(Department::class, 'id')],
        ];
    }

    public function getQuoteType(): QuoteTypes
    {
        return QuoteTypes::from($this->input('quote_type'));
    }

    public function getDepartmentIds(): array
    {
        return $this->input('department_ids', []);
    }
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quote_type.required' => 'The quote type is required',
            'quote_type.string' => 'The quote type must be a string',
        ];
    }
}
