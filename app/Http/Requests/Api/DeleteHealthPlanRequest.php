<?php

namespace App\Http\Requests\Api;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Rules\HealthPlanStatusValidRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeleteHealthPlanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'id' => ['bail', 'integer', new HealthPlanStatusValidRule('Only draft or scheduled health plans can be deleted', [HealthPlanRateSheetStatusEnum::DRAFT->value, HealthPlanRateSheetStatusEnum::SCHEDULED->value])],
        ];
    }

    public function messages(): array
    {
        return [
            'id.integer' => 'Id must be an integer',
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
