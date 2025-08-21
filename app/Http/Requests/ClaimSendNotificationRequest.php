<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\Logger\LoggerService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ClaimSendNotificationRequest extends FormRequest
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
            'customer_message' => 'required|string',
            'ai_optimized_message' => 'required|string',
            'claim_sub_status_id' => 'required|exists:claim_statuses,id',
        ];
    }

    /**
     * Get the validation error messages.
     */
    public function messages(): array
    {
        return [
            'customer_message.required' => 'Customer message is required.',
            'customer_message.string' => 'Customer message must be a valid text.',
            'ai_optimized_message.required' => 'AI optimized message is required.',
            'ai_optimized_message.string' => 'AI optimized message must be a valid text.',
            'claim_sub_status_id.required' => 'Claim sub-status is required.',
            'claim_sub_status_id.exists' => 'Selected claim sub-status is invalid.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'customer_message' => 'customer message',
            'ai_optimized_message' => 'AI optimized message',
            'claim_sub_status_id' => 'claim sub-status',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from string fields
        $this->merge([
            'customer_message' => $this->customer_message ? trim($this->customer_message) : null,
            'ai_optimized_message' => $this->ai_optimized_message ? trim($this->ai_optimized_message) : null,
        ]);
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $errors = $validator->errors();

        // Log validation failures for debugging
        LoggerService::info('Claim send notification validation failed', extra: [
            'errors' => $errors->toArray(),
            'user_id' => Auth::id(),
            'claim_uuid' => $this->route('claimStatus') ?? $this->route('claim'),
            'input_data' => $this->except(['password', 'password_confirmation']),
        ]);

        parent::failedValidation($validator);
    }
}
