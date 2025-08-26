<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GenericRequestEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClaimMakeAdditionalContactPrimaryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'key' => [
                'required',
                'string',
                Rule::in([GenericRequestEnum::EMAIL, GenericRequestEnum::MOBILE_NO])
            ],
            'value' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'key' => 'contact type',
            'value' => 'contact value',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'key.required' => 'Contact type is required.',
            'key.in' => 'Contact type must be either email or mobile_no.',
            'value.required' => 'Contact value is required.',
            'value.string' => 'Contact value must be a valid string.',
            'value.max' => 'Contact value cannot exceed 255 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateContactValue($validator);
        });
    }

    /**
     * Validate the contact value based on the contact type.
     */
    protected function validateContactValue($validator): void
    {
        $key = $this->input('key');
        $value = $this->input('value');

        if (!$key || !$value) {
            return;
        }

        if ($key === GenericRequestEnum::EMAIL) {
            // Validate email format
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $validator->errors()->add('value', 'The contact value must be a valid email address.');
            }
        } elseif ($key === GenericRequestEnum::MOBILE_NO) {
            // Validate mobile number format
            if (!preg_match('/^[\+]?[0-9\s\-\(\)]+$/', $value)) {
                $validator->errors()->add('value', 'The contact value must be a valid mobile number.');
            }
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'key' => strtolower(trim($this->key ?? '')),
            'value' => trim($this->value ?? ''),
        ]);
    }
}
