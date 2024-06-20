<?php

namespace App\Http\Requests;

use App\Enums\DocumentTypeCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SendUpdateLog;
use Illuminate\Foundation\Http\FormRequest;

class UpdateToCustomerRequest extends FormRequest
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
            'sendUpdateId' => 'required|exists:send_update_logs,id',
            'quoteType' => 'required|string',
            'action' => 'string',
            'quoteUuid' => 'required|string',
            'quoteRefId' => 'required|integer',
            'paymentValidated' => 'required|boolean',
        ];
    }
}
