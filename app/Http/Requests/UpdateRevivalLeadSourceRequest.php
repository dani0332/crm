<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRevivalLeadSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quote_uuid' => 'required|string',
            'quoteTypeId' => 'required|integer',
            'channel' => 'required|string',
            'cta' => 'required|string',
            'medium' => 'optional|string',
        ];
    }
}
