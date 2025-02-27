<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TravelPlanUpdateManualProcessRequest extends FormRequest
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
            'travel_quote_uuid' => 'required|string',
            'current_url' => 'required',
            'travel_plan_id' => 'required|integer',
            'actual_premium' => 'required|numeric',
            'is_create' => 'nullable|in:0,1',
            'discounted_premium' => 'nullable|numeric',
            'addons' => 'nullable|array',
            'addons.*' => 'nullable|array',
        ];
    }
}
