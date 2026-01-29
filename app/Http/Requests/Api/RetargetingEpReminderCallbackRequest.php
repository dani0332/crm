<?php

namespace App\Http\Requests\Api;

use App\Enums\QuoteTypeId;
use Illuminate\Foundation\Http\FormRequest;

class RetargetingEpReminderCallbackRequest extends FormRequest
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
            'uuid' => 'required|string',
            'quoteTypeId' => 'required|integer|in:'.QuoteTypeId::Car,
            'quoteId' => 'required|integer|exists:car_quote_request,id',
            'templateId' => 'required|string',
            'message_id' => 'required|string',
            'customerId' => 'required|integer',
            'customer_email' => 'required|string|email',
            'subject' => 'required|string',
            'responseCode' => 'required',
            'reminderNumber' => 'required|integer|in:1,2',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quoteTypeId.required' => 'Quote type ID is required',
            'quoteTypeId.integer' => 'Quote type ID must be an integer',
            'quoteTypeId.in' => 'Quote type ID must be a valid car quote type',
            'quoteId.required' => 'Quote ID is required',
            'quoteId.integer' => 'Quote ID must be an integer',
            'quoteId.exists' => 'The specified quote ID does not exist',
            'customer_email.required' => 'Customer email is required',
            'customer_email.string' => 'Customer email must be a string',
            'customer_email.email' => 'Customer email must be a valid email address',
            'message_id.required' => 'Message ID is required',
            'message_id.string' => 'Message ID must be a string',
            'customerId.required' => 'Customer ID is required',
            'customerId.integer' => 'Customer ID must be an integer',
            'subject.required' => 'Subject is required',
            'subject.string' => 'Subject must be a string',
            'responseCode.required' => 'Response code is required',
            'reminderNumber.required' => 'Reminder number is required',
            'reminderNumber.integer' => 'Reminder number must be an integer',
            'reminderNumber.in' => 'Reminder number must be 1 or 2',
        ];
    }
}
