<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClaimOptimizeMessageRequest extends FormRequest
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
            'message' => 'required|string|max:1000',
            'claim_uuid' => 'required|string|max:255|exists:claim_requests,uuid',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'message' => 'message',
            'claim_uuid' => 'claim reference',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'message.required' => 'The message field is required.',
            'message.string' => 'The message must be a valid text.',
            'message.max' => 'The message may not exceed 1000 characters.',
            'claim_uuid.required' => 'The claim reference is required.',
            'claim_uuid.string' => 'The claim reference must be a valid text.',
            'claim_uuid.max' => 'The claim reference may not exceed 255 characters.',
            'claim_uuid.exists' => 'The selected claim reference is invalid.',
        ];
    }
}
