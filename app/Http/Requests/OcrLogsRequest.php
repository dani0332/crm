<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OcrLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string',
            'id' => 'required|integer',
        ];
    }
}
