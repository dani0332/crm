<?php

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ClaimUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can(PermissionsEnum::CLAIM_EDIT);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $claimId = $this->route('claim')?->id ?? $this->route('id');

        return [
            // Required basic fields
            'first_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\'-\.]+$/',
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\'-\.]+$/',
            ],
            'phone_number' => [
                'required',
                'string',
                'max:20',
                'regex:/^[\+]?[0-9\s\-\(\)]+$/',
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
            ],
            'line_of_business_id' => [
                'required',
                'integer',
                'exists:quote_types,id',
            ],
            'claim_type_id' => [
                'required',
                'integer',
                'exists:lookups,id',
            ],

            // Optional fields
            'insurer_claim_number' => [
                'nullable',
                'string',
                'max:100',
            ],
            'claim_sub_status_id' => [
                'nullable',
                'integer',
                'exists:lookups,id',
            ],
            'policy_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            // Vehicle fields (for Car/Bike LOB)
            'plate_number' => [
                'nullable',
                'string',
                'max:20',
                'required_if:line_of_business_id,' . $this->getCarBikeLineOfBusinessIds(),
            ],
            'vehicle_make' => [
                'nullable',
                'string',
                'max:100',
                'required_if:line_of_business_id,' . $this->getCarBikeLineOfBusinessIds(),
            ],
            'vehicle_model' => [
                'nullable',
                'string',
                'max:100',
                'required_if:line_of_business_id,' . $this->getCarBikeLineOfBusinessIds(),
            ],
            'vehicle_year' => [
                'nullable',
                'integer',
                'min:1900',
                'max:' . (date('Y') + 1),
                'required_if:line_of_business_id,' . $this->getCarBikeLineOfBusinessIds(),
            ],

            // Assignment fields
            'assigned_claims_manager_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'claims_manager_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'claims_manager_assigned_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            // Status and follow-up fields
            'claim_status' => [
                'nullable',
                'string',
                'in:pending,in_progress,resolved,closed,cancelled',
            ],
            'next_follow_up_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'complaint_status' => [
                'nullable',
                'string',
                'in:none,pending,resolved,escalated',
            ],

            // Reference ID should not be updated manually
            'ref_id' => [
                'sometimes',
                'string',
                'max:20',
            ],
        ];
    }

    /**
     * Get the validation error messages.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.regex' => 'First name contains invalid characters.',
            'last_name.required' => 'Last name is required.',
            'last_name.regex' => 'Last name contains invalid characters.',
            'phone_number.required' => 'Phone number is required.',
            'phone_number.regex' => 'Phone number format is invalid.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Email address must be in a valid format.',
            'line_of_business_id.required' => 'Line of business is required.',
            'line_of_business_id.exists' => 'Selected line of business is invalid.',
            'claim_type_id.required' => 'Claim type is required.',
            'claim_type_id.exists' => 'Selected claim type is invalid.',
            'claim_sub_status_id.exists' => 'Selected claim sub-status is invalid.',
            'assigned_claims_manager_id.exists' => 'Selected assigned claims manager is invalid.',
            'claims_manager_id.exists' => 'Selected claims manager is invalid.',
            'claims_manager_assigned_date.before_or_equal' => 'Claims manager assigned date cannot be in the future.',
            'claim_status.in' => 'Selected claim status is invalid.',
            'next_follow_up_date.after_or_equal' => 'Next follow-up date must be today or in the future.',
            'complaint_status.in' => 'Selected complaint status is invalid.',
            'vehicle_year.min' => 'Vehicle year must be after 1900.',
            'vehicle_year.max' => 'Vehicle year cannot be more than one year in the future.',

            // Required if conditions
            'plate_number.required_if' => 'Plate number is required for Car/Bike claims.',
            'vehicle_make.required_if' => 'Vehicle make is required for Car/Bike claims.',
            'vehicle_model.required_if' => 'Vehicle model is required for Car/Bike claims.',
            'vehicle_year.required_if' => 'Vehicle year is required for Car/Bike claims.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'phone_number' => 'phone number',
            'email' => 'email address',
            'line_of_business_id' => 'line of business',
            'claim_type_id' => 'claim type',
            'claim_sub_status_id' => 'claim sub-status',
            'insurer_claim_number' => 'insurer claim number',
            'policy_number' => 'policy number',
            'plate_number' => 'plate number',
            'vehicle_make' => 'vehicle make',
            'vehicle_model' => 'vehicle model',
            'vehicle_year' => 'vehicle year',
            'assigned_claims_manager_id' => 'assigned claims manager',
            'claims_manager_id' => 'claims manager',
            'claims_manager_assigned_date' => 'claims manager assigned date',
            'claim_status' => 'claim status',
            'next_follow_up_date' => 'next follow-up date',
            'complaint_status' => 'complaint status',
            'ref_id' => 'reference ID',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from string fields
        $this->merge([
            'first_name' => $this->first_name ? trim($this->first_name) : null,
            'last_name' => $this->last_name ? trim($this->last_name) : null,
            'email' => $this->email ? trim(strtolower($this->email)) : null,
            'phone_number' => $this->phone_number ? trim($this->phone_number) : null,
            'insurer_claim_number' => $this->insurer_claim_number ? trim($this->insurer_claim_number) : null,
            'policy_number' => $this->policy_number ? trim($this->policy_number) : null,
            'plate_number' => $this->plate_number ? trim(strtoupper($this->plate_number)) : null,
            'vehicle_make' => $this->vehicle_make ? trim($this->vehicle_make) : null,
            'vehicle_model' => $this->vehicle_model ? trim($this->vehicle_model) : null,
        ]);

        // Remove ref_id from input to prevent manual updates
        if ($this->has('ref_id')) {
            $this->getInputSource()->remove('ref_id');
        }
    }

    /**
     * Get the Car and Bike line of business IDs for conditional validation
     */
    protected function getCarBikeLineOfBusinessIds(): string
    {
        // These values should match the actual IDs in your quote_types table
        // You might want to get these dynamically from the database
        return '1,2'; // Assuming 1=Car, 2=Bike - adjust as needed
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->sometimes('next_follow_up_date', 'after:today', function ($input) {
            // Only require future date if status is not resolved or closed
            return !in_array($input->claim_status, ['resolved', 'closed', 'cancelled']);
        });
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();

        // Log validation failures for debugging
        \Log::info('Claim update validation failed', [
            'errors' => $errors->toArray(),
            'input' => $this->except(['password', 'password_confirmation']),
            'user_id' => Auth::id(),
            'claim_id' => $this->route('claim')?->id ?? $this->route('id'),
        ]);

        parent::failedValidation($validator);
    }
}
