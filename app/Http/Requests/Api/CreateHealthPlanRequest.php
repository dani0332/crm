<?php

namespace App\Http\Requests\Api;

use App\Enums\HealthBusinessTypeEnum;
use App\Enums\HealthPlanTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateHealthPlanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => 'required|unique:health_plan,code',
            'text' => 'required',
            'health_business_type' => ['required', new Enum(HealthBusinessTypeEnum::class)],
            'health_plan_type' => ['nullable', new Enum(HealthPlanTypeEnum::class)],
            'health_rating_eligibility_id' => 'nullable|exists:health_rating_eligibilities,id',
            'health_network_id' => 'nullable|exists:health_networks,id',
            'is_active' => 'required|boolean',
            'is_hidden' => 'required|boolean',
        ];
    }
}
