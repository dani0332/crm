<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ClaimsEnum;
use Illuminate\Foundation\Http\FormRequest;

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
                'required',
                'integer',
                'exists:claim_statuses,id,is_active,1,status_type,'.ClaimsEnum::CLAIM_STATUSES_STATUS_KEY->value,
            ],
            'notes' => [
                'nullable',
                'string',
                'max:255',
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
            'notes.max' => 'The notes must be less than 255 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'claim_status_id' => 'claim status',
            'notes' => 'notes',
        ];
    }
}
