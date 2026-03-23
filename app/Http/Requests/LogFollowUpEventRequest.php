<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LogFollowUpEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message_id' => 'required|string',
            'customer_email' => 'nullable|string|email',
            'mobile_no' => ['nullable', 'string', 'regex:/^\+?[0-9]{10,20}$/'],
            'subject' => 'nullable|string',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $data = $v->getData();
            $hasEmail = isset($data['customer_email']) && trim((string) $data['customer_email']) !== '';
            $hasMobile = isset($data['mobile_no']) && trim((string) $data['mobile_no']) !== '';

            if (! $hasEmail && ! $hasMobile) {
                $v->errors()->add('customer_email', 'Either customer_email or mobile_no is required.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'mobile_no.regex' => 'The mobile number must be digits only (optional leading +), 10–20 characters.',
        ];
    }
}
