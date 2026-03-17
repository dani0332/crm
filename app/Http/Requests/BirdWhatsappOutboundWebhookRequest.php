<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BirdWhatsappOutboundWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payload' => 'required|array',
            'payload.id' => 'required|string',
            'payload.status' => 'required|string',
            'payload.reason' => 'nullable|string',
            'payload.receiver' => 'required|array',
            'payload.receiver.contacts' => 'required|array',
            'payload.receiver.contacts.*.id' => 'required|string',
            'payload.receiver.contacts.*.identifierKey' => 'nullable|string',
            'payload.receiver.contacts.*.identifierValue' => ['required', 'string', 'regex:/^\+?[0-9\s\-]{10,20}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payload.receiver.contacts.*.identifierValue.regex' => 'The contact identifier must be a valid phone number.',
        ];
    }
}
