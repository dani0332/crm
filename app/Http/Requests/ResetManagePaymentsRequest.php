<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates reset-manage-payments requests. Rules are extended per quote type as LOBs are enabled.
 */
class ResetManagePaymentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'uuid' => ['required', 'string', 'exists:health_quote_request,uuid'],
            'reason' => ['required', 'string', 'min:5', 'max:200'],
        ];
    }
}
