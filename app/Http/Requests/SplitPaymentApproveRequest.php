<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SplitPaymentApproveRequest extends FormRequest
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
            'collection_amount' => 'array',
            'collection_amount.*' => 'nullable|numeric',
            'customer_id' => 'required|integer',
            'declined_custom_reason' => 'nullable|string',
            'declined_reason' => 'nullable|integer',
            'is_approved' => 'required|boolean',
            'is_capture' => 'required|boolean',
            'is_declined' => 'required|boolean',
            'modelType' => 'required|string',
            'plan_id' => 'required|integer',
            'quote_id' => 'required|integer',
        ];
    }
}
