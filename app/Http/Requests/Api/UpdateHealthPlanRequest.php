<?php

namespace App\Http\Requests\Api;

use App\Enums\HealthBusinessTypeEnum;
use App\Rules\HealthPlanGenderValidRule;
use App\Rules\HealthPlanUpdateRateExistsRule;
use App\Rules\HealthPlanUpdateStatusValidRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Enum;

class UpdateHealthPlanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function rules(): array
    {
        return [
            'id' => [
                'integer',
                'exists:health_plan,id',
                new HealthPlanUpdateStatusValidRule('Scheduled plans cannot be updated'),
                new HealthPlanUpdateRateExistsRule,
            ],
            'text' => 'required',
            'text_ar' => 'nullable',
            'health_business_type' => ['required', new Enum(HealthBusinessTypeEnum::class)],
            'plan_type_id' => 'nullable|integer|exists:health_plan_type,id',
            'health_rating_eligibility_id' => 'nullable|integer|exists:health_rating_eligibilities,id',
            'health_network_id' => 'nullable|integer|exists:health_networks,id',
            'provider_id' => 'nullable|integer|exists:insurance_provider,id',
            'cohort_enabled' => 'nullable|boolean',
            'marital_status_enabled' => ['nullable', 'boolean', new HealthPlanGenderValidRule($this->input('gender_enabled'))],
            'gender_enabled' => 'nullable|boolean',
            'is_active' => 'required|boolean',
            'is_hidden' => 'required|boolean',
            'maf_link' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'id.exists' => 'Plan not found',
            'required' => ':attribute is required',
            'unique' => ':attribute already exists',
            'health_business_type.required' => 'Health business type is required',
            'plan_type_id.exists' => 'Plan type does not exist',
            'health_rating_eligibility_id.exists' => 'Health rating eligibility does not exist',
            'health_network_id.exists' => 'Health network does not exist',
            'provider_id.exists' => 'Provider does not exist',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $messages = collect($validator->errors()->all());

        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'errors' => $messages,
            ], 422)
        );
    }
}
