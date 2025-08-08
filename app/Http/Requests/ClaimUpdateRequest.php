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
        return true;
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
            'mobile_no' => [
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
            'quote_type_id' => [
                'required',
                'integer',
                'exists:quote_type,id',
            ],
            'claim_type_id' => [
                'required',
                'integer',
                'exists:lookups,id',
            ],

            // Optional fields
            'customer_id' => [
                'nullable',
                'integer',
            ],
            'insurance_provider_id' => [
                'nullable',
                'integer',
                'exists:insurance_provider,id',
            ],
            'claim_number' => [
                'nullable',
                'string',
                'max:100',
            ],
            'policy_number' => [
                'nullable',
                'string',
                'max:100',
            ],
            'incident_date' => [
                'nullable',
                'date',
            ],
            'incident_story' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'source' => [
                'nullable',
                'string',
                'max:50',
            ],

            // Policy selection fields
            'selected_policy_id' => [
                'nullable',
                'integer',
            ],
            'selected_quote_uuid' => [
                'nullable',
                'string',
                'max:255',
            ],
            'policy_not_listed' => [
                'nullable',
                'boolean',
            ],

            // Car-specific fields
            'plate_number' => [
                'nullable',
                'string',
                'max:20',
            ],
            'car_make' => [
                'nullable',
                'string',
                'max:100',
            ],
            'car_model' => [
                'nullable',
                'string',
                'max:100',
            ],
            'car_model_year' => [
                'nullable',
                'integer',
                'min:1900',
                'max:'.(date('Y') + 1),
            ],

            // Financial fields
            'approved_repair_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'approved_total_loss_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'approved_cash_loss_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'claim_denial_reason' => [
                'nullable',
                'string',
                'max:2000',
            ],

            // Health-specific fields
            'claim_request_type_id' => [
                'nullable',
                'integer',
                'exists:lookups,id',
            ],
            'service_type_id' => [
                'nullable',
                'integer',
                'exists:lookups,id',
            ],
            'request_reference_number' => [
                'nullable',
                'string',
                'max:100',
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
            'mobile_no.required' => 'Phone number is required.',
            'mobile_no.regex' => 'Phone number format is invalid.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Email address must be in a valid format.',
            'quote_type_id.required' => 'Line of business is required.',
            'quote_type_id.exists' => 'Selected line of business is invalid.',
            'claim_type_id.required' => 'Claim type is required.',
            'claim_type_id.exists' => 'Selected claim type is invalid.',
            'car_model_year.min' => 'Vehicle year must be after 1900.',
            'car_model_year.max' => 'Vehicle year cannot be more than one year in the future.',
            'approved_repair_amount.numeric' => 'Approved repair amount must be a number.',
            'approved_repair_amount.min' => 'Approved repair amount must be greater than or equal to 0.',
            'approved_total_loss_amount.numeric' => 'Approved total loss amount must be a number.',
            'approved_total_loss_amount.min' => 'Approved total loss amount must be greater than or equal to 0.',
            'approved_cash_loss_amount.numeric' => 'Approved cash loss amount must be a number.',
            'approved_cash_loss_amount.min' => 'Approved cash loss amount must be greater than or equal to 0.',
            'customer_id.integer' => 'Customer ID must be a number.',
            'insurance_provider_id.exists' => 'Selected insurance provider is invalid.',
            'claim_request_type_id.exists' => 'Selected claim request type is invalid.',
            'service_type_id.exists' => 'Selected service type is invalid.',
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
            'mobile_no' => 'phone number',
            'email' => 'email address',
            'quote_type_id' => 'line of business',
            'claim_type_id' => 'claim type',
            'policy_number' => 'policy number',
            'plate_number' => 'plate number',
            'car_make' => 'vehicle make',
            'car_model' => 'vehicle model',
            'car_model_year' => 'vehicle year',
            'customer_id' => 'customer ID',
            'insurance_provider_id' => 'insurance provider',
            'claim_number' => 'claim number',
            'incident_date' => 'incident date',
            'incident_story' => 'incident story',
            'approved_repair_amount' => 'approved repair amount',
            'approved_total_loss_amount' => 'approved total loss amount',
            'approved_cash_loss_amount' => 'approved cash loss amount',
            'claim_denial_reason' => 'claim denial reason',
            'claim_request_type_id' => 'claim request type',
            'service_type_id' => 'service type',
            'request_reference_number' => 'request reference number',
            'selected_policy_id' => 'selected policy',
            'selected_quote_uuid' => 'selected quote',
            'policy_not_listed' => 'policy not listed',
            'source' => 'source',
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
            'mobile_no' => $this->mobile_no ? trim($this->mobile_no) : null,
            'claim_number' => $this->claim_number ? trim($this->claim_number) : null,
            'policy_number' => $this->policy_number ? trim($this->policy_number) : null,
            'plate_number' => $this->plate_number ? trim(strtoupper($this->plate_number)) : null,
            'car_make' => $this->car_make ? trim($this->car_make) : null,
            'car_model' => $this->car_model ? trim($this->car_model) : null,
            'incident_story' => $this->incident_story ? trim($this->incident_story) : null,
            'claim_denial_reason' => $this->claim_denial_reason ? trim($this->claim_denial_reason) : null,
            'request_reference_number' => $this->request_reference_number ? trim($this->request_reference_number) : null,
        ]);
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();

        // Log validation failures for debugging
        \App\Services\Logger\LoggerService::info('Claim update validation failed', extra: [
            'errors' => $errors->toArray(),
            'input' => $this->except(['password', 'password_confirmation']),
            'user_id' => Auth::id(),
            'claim_id' => $this->route('claim')?->id ?? $this->route('id'),
        ]);

        parent::failedValidation($validator);
    }
}
