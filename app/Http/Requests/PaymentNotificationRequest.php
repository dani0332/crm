<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentNotificationRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'quoteType' => 'required|string',
            'quoteId' => 'required',
        ];
    }

    /**
     * Custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'quoteType.required' => 'Quote Type is required',
            'quoteType.string' => 'Quote Type must be a string',
            'quoteId.required' => 'Quote ID is required',
        ];
    }
}
