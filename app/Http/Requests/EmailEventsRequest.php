<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailEventsRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message_id' => 'required|string',
            'customer_email' => 'required_without:mobile|nullable|string',
            'mobile' => 'required_without:customer_email|nullable|string|regex:/^\+?[0-9\s\-]{10,20}$/',
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
            'mobile.regex' => 'The mobile number must be a valid phone number.',
        ];
    }
}
