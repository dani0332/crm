<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UaePassLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quote_uuid' => 'required|string',
            'quote_type_id' => 'required|integer',
        ];
    }
}
