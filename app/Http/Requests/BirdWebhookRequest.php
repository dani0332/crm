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
            'results' => 'nullable |array',
            'results.0' => 'nullable|array',
            'results.0.type' => 'nullable|string',
            'results.0.receiver' => 'nullable|array',
            'results.0.receiver.connector' => 'nullable|array',
            'results.0.receiver.connector.0.identifierValue' => 'nullable|string',
            'results.0.receiver.contacts' => 'nullable|array',
            'results.0.receiver.contacts.0.identifierValue' => 'nullable|string',
            'receiver.contacts' => 'nullable|array',
            'receiver.contacts.0.identifierValue' => 'nullable|string',
            'id' => 'nullable|string',
            'status' => 'nullable|string',
            'reason' => 'nullable|string',
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
