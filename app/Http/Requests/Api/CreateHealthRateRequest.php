<?php

namespace App\Http\Requests\Api;

use App\Enums\EmirateTypeEnum;
use App\Rules\HealthRateCohortValidRule;
use App\Rules\HealthRateGenderValidRule;
use App\Rules\HealthRateMaritalStatusValidRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Enum;

class CreateHealthRateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'health_plan_id' => ['bail', 'required', 'integer', 'exists:health_plan,id'],
            'health_plan_co_payment_id' => ['bail', 'required', 'integer', 'exists:health_plan_co_payments,id'],
            'emirate_type' => ['bail', 'required', new Enum(EmirateTypeEnum::class)],
            'min_age' => ['bail', 'required', 'integer', 'min:0'],
            'max_age' => ['bail', 'required', 'integer', 'gte:min_age'],
            'gender' => new HealthRateGenderValidRule($this->health_plan_id),
            'cohort' => new HealthRateCohortValidRule($this->health_plan_id),
            'marital_status' => new HealthRateMaritalStatusValidRule($this->health_plan_id, $this->gender),
            'premium' => ['bail', 'required', 'integer', 'min:1'],
            'is_active' => ['bail', 'required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute is required',
            'integer' => ':attribute must be an integer',
            'min' => ':attribute must be greater than 0',
            'gte' => ':attribute must be greater than or equal to :value',
            'health_plan_id.exists' => 'Health plan does not exist',
            'health_plan_co_payment_id.exists' => 'Health plan co payment does not exist',
            'emirate_type.enum' => 'Emirate type must be a valid emirate type',
            'boolean' => ':attribute must be a boolean',
            'string' => ':attribute must be a string',
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
