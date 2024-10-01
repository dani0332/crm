<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendUpdateRequest extends FormRequest
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
            'quoteType' => 'required',
            'quoteRefId' => 'required',
            'quoteUuid' => 'required',
            'sendUpdateId' => 'required|exists:send_update_logs,id',
            'inslyMigrated' => 'boolean',
        ];
    }

}
