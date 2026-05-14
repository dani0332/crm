<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates reset-manage-payments requests.
 * Pass uuid and/or quote_request_id (at least one); Health rows live in health_quote_request.
 * For other LOBs, extend rules when implemented; the controller resolves models via getQuoteObject().
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quote_code' => ['required', 'string'],
            'quote_request_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:5', 'max:200'],
        ];
    }
}
