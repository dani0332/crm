<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ClaimBulkAssignRequest extends FormRequest
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
            'claim_uuids' => [
                'required',
                'array',
                'min:1',
            ],
            'claim_uuids.*' => [
                'required',
                'string',
                'exists:claim_requests,uuid',
            ],
            'manager_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ];
    }

    /**
     * Get the validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'claim_uuids.required' => 'Please select at least one claim.',
            'manager_id.required' => 'Please select a claims manager to assign.',
            'manager_id.exists' => 'The selected claims manager is invalid.',
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
            'claim_uuids' => 'claims',
            'manager_id' => 'claims manager',
        ];
    }
}
