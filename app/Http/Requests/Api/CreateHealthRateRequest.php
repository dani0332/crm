<?php

namespace App\Http\Requests\Api;

use App\Enums\EmirateTypeEnum;
use App\Enums\GenderEnum;
use App\Models\HealthPlan;
use App\Rules\HealthPlanRateScheduledRule;
use App\Rules\HealthRateCohortValidRule;
use App\Rules\HealthRateGenderValidRule;
use App\Rules\HealthRateMaritalStatusValidRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Enum;

class CreateHealthRateRequest extends FormRequest
{
    protected ?HealthPlan $plan = null;
    public function rules(): array
    {
        return [
            'effective_from' => ['bail', 'required', 'date', 'after:today'],
            'effective_to' => ['bail', 'required', 'date', 'after:effective_from'],
            'health_plan_id' => ['bail', 'required', 'integer', 'exists:health_plan,id', new HealthPlanRateScheduledRule],
            'health_plan_co_payment_id' => ['bail', 'required', 'integer', 'exists:health_plan_co_payments,id'],
            'emirate_type' => ['bail', 'required', new Enum(EmirateTypeEnum::class)],
            'min_age' => ['bail', 'required', 'integer', 'min:0'],
            'max_age' => ['bail', 'required', 'integer', 'gte:min_age'],
            'gender' => new HealthRateGenderValidRule($this->health_plan_id),
            'cohort' => new HealthRateCohortValidRule($this->health_plan_id),
            'marital_status' => new HealthRateMaritalStatusValidRule($this->health_plan_id, $this->gender),
            'premium' => ['bail', 'required', 'numeric', 'min:0.01', 'regex:/^\d+(\.\d{1,2})?$/'],
            'user_id' => ['bail', 'required', 'integer', 'exists:users,id'],
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
            'effective_to.after' => 'Effective to must be greater than effective from',
            'user_id.exists' => 'User does not exist',
            'premium.regex' => 'Premium can be a decimal upto 2 digits',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $this->plan = HealthPlan::find($this->health_plan_id);

        $validator->sometimes('gender', 'required', $this->requires('gender_enabled'));
        $validator->sometimes('cohort', 'required', $this->requires('cohort_enabled'));
        $validator->sometimes('marital_status', 'required', fn () => $this->plan?->marital_status_enabled && strtolower($this->gender) == GenderEnum::FEMALE->value);
    }

    private function requires(string $flag)
    {
        return fn () => $this->plan?->{$flag};
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
