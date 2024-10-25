<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualPlanToggleRequest extends FormRequest
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
            'modelType' => 'required|string|in:PersonalQuote',
            'planIds' => 'required|array|min:1',
            'personal_quote_uuid' => 'required|string',
            'toggle' => 'required|boolean'
        ];
    }

    public function messages()
    {
        return [
            'modelType.required' => 'The model type is required.',
            'modelType.in' => 'The selected model type is invalid.',
            'planIds.required' => 'At least one plan ID is required.',
            'planIds.*.integer' => 'Each plan ID must be an integer.',
            'personal_quote_uuid.required' => 'The personal quote UUID is required.',
            'toggle.required' => 'The toggle field is required.',
            'toggle.boolean' => 'The toggle must be true or false.'
        ];
    }
}
