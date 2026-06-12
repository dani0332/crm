<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ClaimsEnum;
use Carbon\Carbon;
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
                'required',
                'integer',
                'exists:claim_statuses,id,status_type,'.ClaimsEnum::CLAIM_STATUSES_COMPLAINT_STATUS_KEY->value,
            ],
            'complaint_datetime' => [
                'required',
                'date',
                function (string $_attribute, mixed $value, \Closure $fail): void {
                    $errorMessage = 'The complaint date must be a valid date.';
                    // Guard against non-string/non-parseable values
                    if (! is_string($value) && ! is_numeric($value) && ! $value instanceof \DateTimeInterface) {
                        $fail($errorMessage);

                        return;
                    }

                    try {
                        // Parse the normalized datetime and compare with end of today
                        $complaintDateTime = Carbon::parse($value);
                        $endOfToday = now()->endOfDay();
                        if ($complaintDateTime->isAfter($endOfToday)) {
                            $fail('The complaint date cannot be in the future.');
                        }
                    } catch (\Exception $e) {
                        $fail($errorMessage);
                    }
                },
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
            'complaint_status_id.required' => 'The complaint status field is required.',
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

        // Handle datetime conversion from frontend (same as NextFollowUp)
        if ($this->has('complaint_datetime')) {
            $dateValue = $this->input('complaint_datetime');

            // If it's a JavaScript Date object string or ISO format, convert it
            if (is_string($dateValue)) {
                try {
                    $date = new \DateTime($dateValue);
                    $formattedDate = $date->format('Y-m-d H:i:s');

                    $this->merge([
                        'complaint_datetime' => $formattedDate,
                    ]);
                } catch (\Exception $e) {
                    // Leave as-is; validation will fail with date rule
                }
            }
        }
    }
}
