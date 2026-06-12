<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\QuoteFlowType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LogFollowUpEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $body = $this->input('body');
        if (! is_array($body)) {
            $body = is_string($body) ? json_decode($body, true) : null;
        }
        if (! is_array($body)) {
            return;
        }
        if (isset($body['quote_type_id'])) {
            $body['quoteTypeId'] ??= $body['quote_type_id'];
        }
        $this->merge($body);
    }

    public function rules(): array
    {
        return [
            'message_id' => 'required|string',
            'customer_email' => 'nullable|string|email',
            'mobile_no' => ['nullable', 'string', 'regex:/^\+?[0-9]{10,20}$/'],
            'subject' => 'nullable|string',
            'flow_type' => ['nullable', 'integer', Rule::enum(QuoteFlowType::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $d = $v->getData();
            $email = trim((string) ($d['customer_email'] ?? ''));
            $mobile = trim((string) ($d['mobile_no'] ?? ''));
            if ($email === '' && $mobile === '') {
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
