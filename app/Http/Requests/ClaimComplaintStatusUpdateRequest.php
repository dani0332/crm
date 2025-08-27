<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClaimComplaintStatusUpdateRequest extends FormRequest
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
            'complaint_status_id' => [
                'nullable',
                'integer',
                'exists:claim_statuses,id',
            ],
            'complaint_datetime' => [
                'required',
                'date',
                'before_or_equal:now',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:250',
            ],
        ];
    }

    /**
     * Get the validation error messages.
     */
    public function messages(): array
    {
        return [
            'complaint_status_id.exists' => 'The selected complaint status is invalid.',
            'complaint_datetime.required' => 'The complaint date field is required.',
            'complaint_datetime.date' => 'The complaint date must be a valid date.',
            'complaint_datetime.before_or_equal' => 'The complaint date cannot be in the future.',
            'notes.max' => 'The notes must be less than 250 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'complaint_status_id' => 'complaint status',
            'complaint_datetime' => 'complaint date',
            'notes' => 'notes',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('notes') && ! empty($this->input('notes'))) {
            $this->merge([
                'notes' => trim($this->input('notes', '')),
            ]);
        }
    }
}
