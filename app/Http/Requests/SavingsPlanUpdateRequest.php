<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavingsPlanUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'quote_uuid' => 'required|string',
            'plan_id' => 'required|integer',
            'provider_name' => 'required|string',
            'actual_premium' => 'nullable|numeric|min:0',
            'insurer_quote_no' => 'nullable|string|max:255',
            'is_disabled' => 'boolean',
            'is_manual_update' => 'boolean',
            'current_url' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'quote_uuid.required' => 'The quote UUID is required.',
            'plan_id.required' => 'The plan ID is required.',
            'plan_id.integer' => 'The plan ID must be an integer.',
            'provider_name.required' => 'The provider name is required.',
            'actual_premium.numeric' => 'The actual premium must be a valid number.',
            'actual_premium.min' => 'The actual premium must be at least 0.',
            'insurer_quote_no.max' => 'The insurer quote number cannot exceed 255 characters.',
            'is_disabled.boolean' => 'The disabled status must be true or false.',
            'is_manual_update.boolean' => 'The manual update status must be true or false.',
        ];
    }
}
