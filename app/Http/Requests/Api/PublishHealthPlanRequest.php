<?php

namespace App\Http\Requests\Api;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Rules\HealthPlanRateControlPublishableRule;
use App\Rules\HealthPlanStatusValidRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PublishHealthPlanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function rules(): array
    {
        return [
            'id' => [
                'bail',
                'integer',
                new HealthPlanStatusValidRule('Only draft health plans can be published', [HealthPlanRateSheetStatusEnum::DRAFT->value]),
                new HealthPlanRateControlPublishableRule,
            ],
            'published_by_id' => 'bail|required|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'integer' => ':attribute must be an integer',
            'id.exists' => 'Health plan not found',
            'published_by_id.exists' => 'Publish by user not found',
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
