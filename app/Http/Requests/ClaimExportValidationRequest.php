<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ClaimExportValidationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation by converting "null" strings to null.
     */
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        // Recursively replace "null" strings with null
        array_walk_recursive($data, function (&$value) {
            if ($value === 'null') {
                $value = null;
            }
        });

        // Merge the modified data back into the request
        $this->merge($data);
        request()->merge($data); // Sync with global request
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'created_at_start' => 'nullable|date',
            'created_at_end' => 'nullable|date',
            'exportType' => 'nullable|in:email,download',
            'subject' => 'nullable|string|max:255',
            'exportTitle' => 'nullable|string|max:100',

            // Claims-specific filters
            'code' => 'nullable|string|max:50',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'mobile_no' => 'nullable|string|max:20',
            'claim_status_id' => 'nullable|integer|exists:claim_statuses,id',
            'claim_sub_status_id' => 'nullable|integer|exists:claim_statuses,id',
            'manager_id' => 'nullable|integer|exists:users,id',
            'quote_type_id' => 'nullable|integer|exists:quote_type,id',
            'complaint_status_id' => 'nullable|integer|exists:claim_statuses,id',
            'policy_number' => 'nullable|string|max:100',
            'claim_type_id' => 'nullable|integer|exists:lookups,id',
            'claim_request_type_id' => 'nullable|integer|exists:lookups,id',
            'insurance_provider_id' => 'nullable|integer|exists:insurance_provider,id',
            'manager_assigned_date' => 'nullable|date',
            'next_followup_datetime' => 'nullable|date',
            'assigned_status' => 'nullable|in:assigned,un-assigned',

            // Car-specific filters
            'plate_number' => 'nullable|string|max:20',
            'car_make' => 'nullable|string|max:100',
            'car_model' => 'nullable|string|max:100',
            'model_year' => 'nullable|integer|min:1900|max:'.(date('Y') + 1),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'created_at_start' => 'start date',
            'created_at_end' => 'end date',
            'exportType' => 'export type',
            'claim_status_id' => 'claim status',
            'claim_sub_status_id' => 'claim sub-status',
            'manager_id' => 'assigned manager',
            'quote_type_id' => 'line of business',
            'complaint_status_id' => 'complaint status',
            'policy_number' => 'policy number',
            'claim_type_id' => 'claim type',
            'claim_request_type_id' => 'claim request type',
            'insurance_provider_id' => 'insurance provider',
            'manager_assigned_date' => 'manager assigned date',
            'next_followup_datetime' => 'next follow-up date',
            'plate_number' => 'plate number',
            'car_make' => 'car make',
            'car_model' => 'car model',
            'model_year' => 'model year',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'created_at_start.date' => 'The start date must be a valid date.',
            'created_at_end.date' => 'The end date must be a valid date.',
            'exportType.in' => 'The export type must be either email or download.',
            'model_year.min' => 'The model year must be at least 1900.',
            'model_year.max' => 'The model year cannot be more than next year.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $validator->errors()->any()) {
                $this->validateDateRange($validator);
                $this->validateEmailExportRequirements($validator);
            }
        });
    }

    /**
     * Validate date range constraints for claims export
     */
    private function validateDateRange($validator): void
    {
        if ($this->has('created_at_start') && $this->has('created_at_end')) {
            $start = Carbon::parse($this->input('created_at_start'));
            $end = Carbon::parse($this->input('created_at_end'));
            $isEmailExport = $this->input('exportType') === 'email';

            if ($isEmailExport) {
                // For email exports, use months-based validation
                $monthsDiff = $this->calculateMonthsDifference($start, $end);
                $maxMonths = 3;

                if ($monthsDiff > $maxMonths) {
                    $validator->errors()->add('created_at_end', "Maximum of {$maxMonths} months (created date range) are allowed for email exports.");
                }
            } else {
                // For download exports, use days-based validation
                $diffInDays = 31;
                $diff = $start->diffInDays($end);

                if ($diff >= $diffInDays) {
                    $validator->errors()->add('created_at_end', "Maximum of {$diffInDays} days (created date range) are allowed for download exports.");
                }
            }

            // Ensure start date is not after end date
            if ($start->gt($end)) {
                $validator->errors()->add('created_at_start', 'The start date must be before or equal to the end date.');
            }
        }
    }

    /**
     * Validate email export specific requirements
     */
    private function validateEmailExportRequirements($validator): void
    {
        if ($this->input('exportType') === 'email') {
            // no validation required for email exports
        }
    }

    /**
     * Calculate months difference between two dates
     */
    private function calculateMonthsDifference($startDate, $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Calculate year and month difference
        $yearDiff = $end->year - $start->year;
        $monthDiff = $end->month - $start->month;

        // Total months difference
        $totalMonths = $yearDiff * 12 + $monthDiff;

        // Return absolute difference in months
        return abs($totalMonths);
    }
}
