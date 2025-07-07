<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuoteExportLogRequest extends FormRequest
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
            'quote_type_id' => request()->has('type') && (request()->type === 'instant-alfred-chat' || request()->type === 'aml-ctf-report') ? 'nullable' : 'required|exists:quote_type,id',
            'url' => 'required',
        ];
    }
}
