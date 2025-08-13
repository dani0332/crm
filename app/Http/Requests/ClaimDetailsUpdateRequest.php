<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Enums\QuoteTypes;

class ClaimDetailsUpdateRequest extends FormRequest
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
            // Car-specific fields (only when quote_type_id is Car)
            'plat_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Z0-9\-\s]+$/',
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
            'model_year' => [
                'nullable',
                'integer',
                'min:1900',
                'max:' . (date('Y') + 1),
            ],

            // Health-specific fields (only when quote_type_id is Health)
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

            // Common fields that can be updated
            'claim_type_id' => [
                'nullable',
                'integer',
                'exists:lookups,id',
            ],
            'claim_number' => [
                'nullable',
                'string',
                'max:100',
            ],
            'claim_decline_reason' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * Get the validation error messages.
     */
    public function messages(): array
    {
        return [
            'plat_number.regex' => 'Plate number can only contain letters, numbers, hyphens, and spaces.',
            'plat_number.max' => 'Plate number cannot exceed 20 characters.',
            'car_make.max' => 'Vehicle make cannot exceed 100 characters.',
            'car_model.max' => 'Vehicle model cannot exceed 100 characters.',
            'model_year.min' => 'Vehicle year must be after 1900.',
            'model_year.max' => 'Vehicle year cannot be more than one year in the future.',
            'model_year.integer' => 'Vehicle year must be a valid number.',
            'claim_request_type_id.exists' => 'Selected claim request type is invalid.',
            'claim_request_type_id.integer' => 'Claim request type must be a number.',
            'service_type_id.exists' => 'Selected service type is invalid.',
            'service_type_id.integer' => 'Service type must be a number.',
            'claim_type_id.exists' => 'Selected claim type is invalid.',
            'claim_type_id.integer' => 'Claim type must be a number.',
            'claim_number.max' => 'Claim number cannot exceed 100 characters.',
            'claim_decline_reason.max' => 'Claim decline reason cannot exceed 2000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'plat_number' => 'plate number',
            'car_make' => 'vehicle make',
            'car_model' => 'vehicle model',
            'model_year' => 'vehicle year',
            'claim_request_type_id' => 'claim request type',
            'service_type_id' => 'service type',
            'claim_type_id' => 'claim type',
            'claim_number' => 'insurer claim number',
            'claim_decline_reason' => 'claim decline reason',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from string fields and normalize data
        $this->merge([
            'plat_number' => $this->plat_number ? trim(strtoupper($this->plat_number)) : null,
            'car_make' => $this->car_make ? trim($this->car_make) : null,
            'car_model' => $this->car_model ? trim($this->car_model) : null,
            'claim_number' => $this->claim_number ? trim($this->claim_number) : null,
            'claim_decline_reason' => $this->claim_decline_reason ? trim($this->claim_decline_reason) : null,
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation logic can be added here if needed
            $quoteTypeId = request()?->quote_type_id;
            $isCarLob = $quoteTypeId == QuoteTypes::CAR->id();
            $isHealthLob = $quoteTypeId == QuoteTypes::HEALTH->id();
            if($isCarLob){
                $this->validateCarFields($validator);
            }
            if($isHealthLob){
                $this->validateHealthFields($validator);
            }
            if(!request()->claim_type_id){
                $validator->errors()->add('claim_type_id', 'Claim type is required.');
            }
        });
    }

    /**
     * Validate car-specific fields based on context.
     */
    private function validateCarFields($validator): void
    {
        // If car_make is provided, car_model should also be provided (when editing)
        if (!$this->filled('car_model')) {
            $validator->errors()->add('car_model', 'Vehicle model is required when vehicle make is specified.');
        }

        // If car_model is provided, car_make should also be provided
        if (!$this->filled('car_make')) {
            $validator->errors()->add('car_make', 'Vehicle make is required when vehicle model is specified.');
        }

        if (!$this->filled('plat_number')) {
            $validator->errors()->add('plat_number', 'Vehicle plate number is required when vehicle model is specified.');
        }

        if (!$this->filled('model_year')) {
            $validator->errors()->add('model_year', 'Vehicle year is required when vehicle model is specified.');
        }
    }

    /**
     * Validate health-specific fields based on context.
     */
    private function validateHealthFields($validator): void
    {
        // Add any health-specific validation logic here if needed
        if (!$this->filled('claim_request_type_id')) {
            $validator->errors()->add('claim_request_type_id', 'Claim request type is required.');
        }

        if (!$this->filled('service_type_id')) {
            $validator->errors()->add('service_type_id', 'Service type is required.');
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $errors = $validator->errors();

        // Log validation failures for debugging
        \App\Services\Logger\LoggerService::info('Claim details update validation failed', extra: [
            'errors' => $errors->toArray(),
            'user_id' => Auth::id(),
            'claim_uuid' => $this->route('uuid'),
        ]);

        parent::failedValidation($validator);
    }

    /**
     * Get the validated data from the request, filtering only the allowed fields.
     */
    public function validatedForUpdate(): array
    {
        $validated = $this->validated();
        
        // Filter out empty strings and keep only non-null values
        return array_filter($validated, function ($value) {
            return $value !== '' && $value !== null;
        });
    }
}
