<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared request validation for log-related endpoints.
 *
 * Used by multiple log controllers (OCR, EP, etc.) that require
 * consistent validation of type and ID parameters.
 */
class LogsRequest extends FormRequest
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
