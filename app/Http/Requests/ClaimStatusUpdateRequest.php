<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClaimStatusUpdateRequest extends FormRequest
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
            'claim_status_id' => [
                'nullable',
                'integer',
                'exists:claim_statuses,id,is_active,1,parent,1',
            ],
            'claim_sub_status_id' => [
                'required',
                'integer',
                'exists:claim_statuses,id,is_active,1,parent,0',
            ],
        ];
    }

    /**
     * Get the validation error messages.
     */
    public function messages(): array
    {
        return [
            'claim_status_id.exists' => 'The selected claim status is invalid or inactive.',
            'claim_sub_status_id.required' => 'The claim sub status is required.',
            'claim_sub_status_id.exists' => 'The selected claim sub status is invalid or inactive.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'claim_status_id' => 'claim status',
            'claim_sub_status_id' => 'claim sub status',
        ];
    }
}
