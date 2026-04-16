<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssignSupportUserRequest extends FormRequest
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
            'support_user_id' => 'required|exists:users,id',
            'assigned_lead_id' => 'required|string',
            'modelType' => 'required|string',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'support_user_id.required' => 'Please select a support user.',
            'support_user_id.exists' => 'The selected support user does not exist.',
            'assigned_lead_id.required' => 'Lead ID is required for assignment.',
            'assigned_lead_id.string' => 'Lead ID must be a valid string.',
            'modelType.required' => 'Model type is required for assignment.',
            'modelType.string' => 'Model type must be a valid string.',
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
            'support_user_id' => 'support user',
            'assigned_lead_id' => 'lead ID',
            'modelType' => 'model type',
        ];
    }
}
