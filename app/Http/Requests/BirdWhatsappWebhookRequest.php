<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BirdWhatsappWebhookRequest extends FormRequest
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
        $base = [
            'payload' => 'required|array',
            'payload.receiver' => 'required|array',
            'payload.receiver.contacts' => 'required|array',
            'payload.receiver.contacts.*.id' => 'required|string',
            'payload.receiver.contacts.*.identifierKey' => 'nullable|string',
            'payload.receiver.contacts.*.identifierValue' => ['required', 'string', 'regex:/^\+?[0-9]{10,20}$/'],
            'payload.messageTags' => 'nullable|array',
            'payload.messageTags.*' => 'string',
        ];

        return match ($this->webhookKind()) {
            'outbound' => array_merge($base, [
                'payload.id' => 'required|string',
                'payload.status' => 'required|string',
                'payload.reason' => 'nullable|string',
            ]),
            'interaction' => array_merge($base, [
                'payload.messageId' => 'required|string',
                'payload.type' => 'nullable|string',
                'payload.status' => 'nullable|string',
            ]),
            default => array_merge($base, [
                'payload.messageId' => 'required|string',
                'payload.type' => 'nullable|string',
                'payload.status' => 'nullable|string',
            ]),
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payload.receiver.contacts.*.identifierValue.regex' => 'The contact identifier must be digits only (optional leading +), 10–20 characters.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if ($this->webhookKind() !== 'interaction') {
            return;
        }

        $validator->after(function (Validator $v): void {
            $type = $this->input('payload.type');
            $status = $this->input('payload.status');
            $typeFilled = is_string($type) && $type !== '';
            $statusFilled = is_string($status) && $status !== '';
            if (! $typeFilled && ! $statusFilled) {
                $v->errors()->add('payload.type', 'Either payload.type or payload.status is required.');
            }
        });
    }

    private function webhookKind(): string
    {
        $path = $this->path();

        if (str_contains($path, 'bird-whatsapp-outbound')) {
            return 'outbound';
        }
        if (str_contains($path, 'bird-whatsapp-interaction')) {
            return 'interaction';
        }

        return 'inbound';
    }
}
