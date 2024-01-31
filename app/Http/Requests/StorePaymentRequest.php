<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
        $rules = [
            'modelType' => 'required',
            'quote_id' => 'required|numeric',
        ];

        if ($this->input('new_payment_structure') === true) {
            $rules = [
                'total_price' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
                'custom_reason' => 'nullable|string',
                'discount_reason' => 'nullable|string',
                'discount_custom_reason' => 'nullable|string',
                'discount_type' => 'nullable|string',
                'frequency' => 'required|string|in:upfront,monthly,quarterly,semi_annual,split_payments,custom',
                'collection_type' => 'required|string|in:broker,insurer', 
                'total_amount' => 'required|numeric|min:0',
                'collection_date' => 'required|date',
                'discount_value' => 'nullable|numeric|min:0',
                'payment_methods' => 'required|string', 
                'plan_id' => 'nullable|integer',
                'insurance_provider_id' => 'nullable|integer', 
                'split_payment_details.split_amount.*' => 'nullable|numeric', 
                'split_payment_details.payment_type.*' => 'nullable|string', 
                'split_payment_details.due_date.*' => 'nullable|date', 
            ];
        }
       return $rules;      
    }
}
