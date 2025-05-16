<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HomePlanUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'plan' => 'required|array',
            'plan.quote_uuid' => 'required|string',
            'plan.home_plan_id' => 'required|integer',
            'plan.provider_name' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'plan.required' => 'The plan data is required.',
            'plan.array' => 'The plan data must be an array.',
            'plan.quote_uuid.required' => 'The quote UUID is required within the plan data.',
            'plan.home_plan_id.required' => 'The home plan ID is required within the plan data.',
            'plan.provider_name.required' => 'The provider name is required within the plan data.',
        ];
    }
}
