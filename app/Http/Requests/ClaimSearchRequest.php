<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ClaimsEnum;
use Illuminate\Foundation\Http\FormRequest;

class ClaimSearchRequest extends FormRequest
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
            // Basic claim information
            'code' => 'nullable|string|max:50',
            'first_name' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\s\-\'\.]+$/',
            ],
            'last_name' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\s\-\'\.]+$/',
            ],
            'email' => 'nullable|email|max:255',
            'mobile_no' => [
                'nullable',
                'string',
                'regex:/^[0-9+\-\s()]+$/',
                'max:20',
            ],

            // Date filters
            'created_at_start' => 'nullable|date',
            'created_at_end' => 'nullable|date|after_or_equal:created_at_start',
            'manager_assigned_date' => 'nullable|date',
            'next_followup_datetime' => 'nullable|date',

            // Status and type filters
            'claim_status_id' => 'nullable|integer|exists:claim_statuses,id,status_type,'.ClaimsEnum::CLAIM_STATUSES_STATUS_KEY->value,
            'claim_sub_status_id' => 'nullable|integer|exists:claim_statuses,id,status_type,'.ClaimsEnum::CLAIM_STATUSES_SUB_STATUS_KEY->value,
            'complaint_status_id' => 'nullable|integer|exists:claim_statuses,id,status_type,'.ClaimsEnum::CLAIM_STATUSES_COMPLAINT_STATUS_KEY->value,
            'claim_type_id' => 'nullable|integer|exists:lookups,id',
            'claim_request_type_id' => 'nullable|integer|exists:lookups,id',

            // Assignment and policy filters
            'manager_id' => 'nullable|integer|exists:users,id',
            'quote_type_id' => 'nullable|integer|exists:quote_type,id',
            'business_type_of_insurance_id' => 'nullable|integer|exists:business_type_of_insurance,id',
            'insurance_provider_id' => 'nullable|integer|exists:insurance_provider,id',
            'policy_number' => 'nullable|string|max:100',
            'assigned_status' => 'nullable|in:assigned,un-assigned',

            // Car-specific filters
            'plate_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[a-zA-Z0-9\s\-]+$/',
            ],
            'car_make' => 'nullable|string|max:100',
            'car_model' => 'nullable|string|max:100',
            'model_year' => 'nullable|integer|min:1900|max:'.(date('Y') + 1),
            'service_type_id' => 'nullable|integer|exists:lookups,id',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'code' => 'claim code',
            'first_name' => 'first name',
            'last_name' => 'last name',
            'email' => 'email address',
            'mobile_no' => 'mobile number',
            'created_at_start' => 'start date',
            'created_at_end' => 'end date',
            'claim_status_id' => 'claim status',
            'claim_sub_status_id' => 'claim sub-status',
            'complaint_status_id' => 'complaint status',
            'claim_request_type_id' => 'claim request type',
            'manager_id' => 'assigned manager',
            'quote_type_id' => 'line of business',
            'policy_number' => 'policy number',
            'assigned_status' => 'assignment status',
            'manager_assigned_date' => 'manager assigned date',
            'next_followup_datetime' => 'next follow-up date',
            'plate_number' => 'plate number',
            'car_make' => 'car make',
            'car_model' => 'car model',
            'model_year' => 'model year',
            'service_type_id' => 'service type',
            'claim_type_id' => 'claim type',
            'insurance_provider_id' => 'insurance provider',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'first_name.regex' => 'The first name may only contain letters, spaces, hyphens, apostrophes, and periods.',
            'last_name.regex' => 'The last name may only contain letters, spaces, hyphens, apostrophes, and periods.',
            'mobile_no.regex' => 'The mobile number format is invalid. It may only contain numbers, plus signs, hyphens, spaces, and parentheses.',
            'plate_number.regex' => 'The plate number may only contain letters, numbers, spaces, and hyphens.',
            'email.email' => 'Please enter a valid email address.',
            'created_at_start.date' => 'The start date must be a valid date.',
            'created_at_end.date' => 'The end date must be a valid date.',
            'created_at_end.after_or_equal' => 'The end date must be equal to or after the start date.',
            'manager_assigned_date.date' => 'The manager assigned date must be a valid date.',
            'next_followup_datetime.date' => 'The next follow-up date must be a valid date.',
            'claim_status_id.exists' => 'The selected claim status is invalid.',
            'claim_sub_status_id.exists' => 'The selected claim sub-status is invalid.',
            'complaint_status_id.exists' => 'The selected complaint status is invalid.',
            'claim_request_type_id.exists' => 'The selected claim request type is invalid.',
            'manager_id.exists' => 'The selected manager is invalid.',
            'quote_type_id.exists' => 'The selected line of business is invalid.',
            'service_type_id.exists' => 'The selected service type is invalid.',
            'claim_type_id.exists' => 'The selected claim type is invalid.',
            'insurance_provider_id.exists' => 'The selected insurance provider is invalid.',
            'assigned_status.in' => 'The assignment status must be either assigned or un-assigned.',
            'model_year.min' => 'The model year must be at least 1900.',
            'model_year.max' => 'The model year cannot be more than next year.',
        ];
    }
}
