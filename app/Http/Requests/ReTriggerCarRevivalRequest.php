<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReTriggerCarRevivalRequest extends FormRequest
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
            'created_at_start' => ['required', 'date'],
            'created_at_end' => ['required', 'date', 'after_or_equal:created_at_start'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5000'],
        ];
    }
}
