<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReTriggerLifeRevivalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'all' => ['required', 'boolean'],
            'debug' => ['required', 'boolean'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5000'],
        ];
    }
}
