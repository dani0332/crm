<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HealthPricingLogsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'quote_request_id' => 'required|integer|exists:health_quote_request,id',
        ];
    }

    public function messages(): array
    {
        return [
            'quote_request_id.exists' => 'Quote not found',
        ];
    }
}
