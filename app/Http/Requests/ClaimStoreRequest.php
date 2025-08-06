<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ClaimStoreRequest extends FormRequest
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
            'customer_id' => [
                'required',
                'integer',
                'exists:personal_quotes,customer_id',
            ],
            'selected_quote_uuid' => [
                'string',
                'nullable',
            ],
            'insurance_provider_id' => [
                'required',
                'integer',
                'exists:insurance_provider,id',
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

            'incident_story' => [
                'nullable',
                'string',
                'max:1000',
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

            // Vehicle fields (for Car/Bike LOB)
            'plate_number' => [
                'nullable',
                'string',
                'max:20',
                /* 'required_if:quote_type_id,'.$this->getCarBikeQuoteTypeIds(), */
            ],
            'vehicle_make' => [
                'nullable',
                'string',
                'max:100',
                /*  'required_if:quote_type_id,'.$this->getCarBikeQuoteTypeIds(), */
            ],
            'vehicle_model' => [
                'nullable',
                'string',
                'max:100',
                /* 'required_if:quote_type_id,'.$this->getCarBikeQuoteTypeIds(), */
            ],
            'vehicle_year' => [
                'nullable',
                'integer',
                'min:1900',
                'max:'.(date('Y') + 1),
                /* 'required_if:quote_type_id,'.$this->getCarBikeQuoteTypeIds(), */
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
            'mobile_no.required' => 'Mobile number is required.',
            'mobile_no.regex' => 'Mobile number format is invalid.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Email address must be in a valid format.',
            'customer_id.required' => 'Customer is required.',
            'customer_id.exists' => 'Selected customer is invalid.',
            'insurance_provider_id.required' => 'Insurance provider is required.',
            'insurance_provider_id.exists' => 'Selected insurance provider is invalid.',
            'quote_type_id.required' => 'Quote type is required.',
            'quote_type_id.exists' => 'Selected quote type is invalid.',
            'claim_type_id.required' => 'Claim type is required.',
            'claim_type_id.exists' => 'Selected claim type is invalid.',
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
            'mobile_no' => 'mobile number',
            'email' => 'email address',
            'customer_id' => 'customer',
            'insurance_provider_id' => 'insurance provider',
            'quote_type_id' => 'quote type',
            'claim_type_id' => 'claim type',
            'incident_story' => 'incident story',
            'claim_number' => 'claim number',
            'policy_number' => 'policy number',
            'plate_number' => 'plate number',
            'vehicle_make' => 'vehicle make',
            'vehicle_model' => 'vehicle model',
            'vehicle_year' => 'vehicle year',
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
            'mobile_no' => $this->mobile_no ? trim($this->mobile_no) : null,
            'email' => $this->email ? trim(strtolower($this->email)) : null,
            'incident_story' => $this->incident_story ? trim($this->incident_story) : null,
            'claim_number' => $this->claim_number ? trim($this->claim_number) : null,
            'policy_number' => $this->policy_number ? trim($this->policy_number) : null,
            'plate_number' => $this->plate_number ? trim(strtoupper($this->plate_number)) : null,
            'vehicle_make' => $this->vehicle_make ? trim($this->vehicle_make) : null,
            'vehicle_model' => $this->vehicle_model ? trim($this->vehicle_model) : null,
        ]);
    }

    /**
     * Get the Car and Bike quote type IDs for conditional validation
     */
    protected function getCarBikeQuoteTypeIds(): string
    {
        // These values should match the actual IDs in your quote_type table
        // You might want to get these dynamically from the database
        return '1,2'; // Assuming 1=Car, 2=Bike - adjust as needed
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();

        // Log validation failures for debugging
        \Log::info('Claim validation failed', [
            'errors' => $errors->toArray(),
            'input' => $this->except(['password', 'password_confirmation']),
            'user_id' => Auth::id(),
        ]);

        parent::failedValidation($validator);
    }
}
