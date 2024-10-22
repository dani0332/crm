<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BirdWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Adjust according to your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'service' => 'required|string',
            'event' => 'required|string',
            'payload.id' => 'required|uuid',
            'payload.type' => 'required|string',
            'payload.createdAt' => 'required|date',
            'payload.messageId' => 'required|uuid',
            'payload.channelId' => 'required|uuid',
            'payload.platformId' => 'required|string',
            'payload.messageReference' => 'required|string',
            'payload.messagePartsCount' => 'required|integer',
            'payload.receiver.contacts' => 'required|array',
            'payload.receiver.contacts.*.id' => 'required|uuid',
            'payload.receiver.contacts.*.identifierKey' => 'required|string',
            'payload.receiver.contacts.*.identifierValue' => 'required|email',
            'payload.details' => 'nullable|string',
            'payload.metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'results.0.receiver.connector.0.identifierValue' => 'connector identifier value',
            'results.0.receiver.contacts.0.identifierValue' => 'contact identifier value',
            'id' => 'message ID',
            'status' => 'status',
            'reason' => 'reason',
        ];
    }

    /**
     * Get the validation error messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'results.required' => 'The results field is required.',
            'results.0.required' => 'The first result must be provided.',
            'results.0.receiver.required' => 'The receiver information is required.',
            // Add more custom messages if needed
        ];
    }
}
