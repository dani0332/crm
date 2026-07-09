<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncEpBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quoteId' => ['required', 'integer'],
            'modelType' => ['required', 'string'],
            'epTransactionId' => ['required', 'integer', 'exists:embedded_transactions,id'],
            'insuranceProviderId' => ['required', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quoteId.required' => 'Quote id is required',
            'modelType.required' => 'Model type is required',
            'epTransactionId.required' => 'Embedded transaction id is required',
            'insuranceProviderId.required' => 'Insurance provider id is required',
        ];
    }
}
