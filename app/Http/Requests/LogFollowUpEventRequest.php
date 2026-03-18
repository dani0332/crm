<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LogFollowUpEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message_id' => 'required|string',
            'customer_email' => 'required_without:mobile|nullable|string',
            'mobile' => 'required_without:customer_email|nullable|string|regex:/^\+?[0-9]{10,20}$/',
            'subject' => 'nullable|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_email.required_without' => 'Either customer_email or mobile is required.',
            'mobile.required_without' => 'Either customer_email or mobile is required.',
            'mobile.regex' => 'The mobile number must be digits only (optional leading +), 10–20 characters.',
        ];
    }
}
