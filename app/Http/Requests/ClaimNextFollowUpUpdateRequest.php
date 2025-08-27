<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ClaimNextFollowUpUpdateRequest extends FormRequest
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
            'next_follow_up_date' => [
                'required',
                'date',
                'after:'.now()->format('Y-m-d H:i:s'),
                'before:'.now()->addDays(15)->format('Y-m-d H:i:s'),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get the validation error messages.
     */
    public function messages(): array
    {
        return [
            'next_follow_up_date.date' => 'The next follow-up date must be a valid date.',
            'next_follow_up_date.after' => 'The next follow-up date must be in the future.',
            'next_follow_up_date.before' => 'The next follow-up date cannot be more than 15 days in the future.',
            'notes.max' => 'The notes must be less than 500 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'next_follow_up_date' => 'next follow-up date',
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

        // Handle datetime conversion from frontend
        if ($this->has('next_follow_up_date')) {
            $dateValue = $this->input('next_follow_up_date');

            // Debug: Log the incoming date value
            \Log::info('NextFollowUp - Incoming date value:', [
                'original_value' => $dateValue,
                'type' => gettype($dateValue),
            ]);

            // If it's a JavaScript Date object string or ISO format, convert it
            if (is_string($dateValue)) {
                try {
                    $date = new \DateTime($dateValue);
                    $formattedDate = $date->format('Y-m-d H:i:s');

                    \Log::info('NextFollowUp - Converted date:', [
                        'formatted_date' => $formattedDate,
                    ]);

                    $this->merge([
                        'next_follow_up_date' => $formattedDate,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('NextFollowUp - Date conversion failed:', [
                        'error' => $e->getMessage(),
                        'original_value' => $dateValue,
                    ]);
                    // Keep original value if conversion fails - let validation handle it
                }
            }
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->has('next_follow_up_date')) {
                $dateValue = $this->input('next_follow_up_date');

                try {
                    $selectedDate = new \DateTime($dateValue);
                    $now = new \DateTime;
                    $maxDate = new \DateTime('+15 days');

                    \Log::info('NextFollowUp - Custom validation:', [
                        'selected_date' => $selectedDate->format('Y-m-d H:i:s'),
                        'current_time' => $now->format('Y-m-d H:i:s'),
                        'max_date' => $maxDate->format('Y-m-d H:i:s'),
                        'is_past' => $selectedDate <= $now,
                        'is_too_far' => $selectedDate > $maxDate,
                    ]);

                    // Check if date is in the past (with a 1-minute buffer to handle processing time)
                    $nowWithBuffer = new \DateTime('-1 minute');
                    if ($selectedDate <= $nowWithBuffer) {
                        \Log::warning('NextFollowUp - Date is in the past');
                        $validator->errors()->add(
                            'next_follow_up_date',
                            'The next follow-up date must be in the future.'
                        );
                    }

                    // Check if date is more than 15 days in the future
                    if ($selectedDate > $maxDate) {
                        \Log::warning('NextFollowUp - Date is too far in the future');
                        $validator->errors()->add(
                            'next_follow_up_date',
                            'The next follow-up date cannot be more than 15 days in the future.'
                        );
                    }

                } catch (\Exception $e) {
                    \Log::error('NextFollowUp - Custom validation failed:', [
                        'error' => $e->getMessage(),
                        'date_value' => $dateValue,
                    ]);
                    $validator->errors()->add(
                        'next_follow_up_date',
                        'The next follow-up date must be a valid date and time.'
                    );
                }
            }
        });
    }
}
