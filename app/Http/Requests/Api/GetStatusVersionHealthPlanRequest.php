<?php

namespace App\Http\Requests\Api;

use App\Enums\HealthPlanRateSheetStatusEnum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Enum;

class GetStatusVersionHealthPlanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['parent_id' => $this->route('parentId')]);
        $this->merge(['status' => $this->route('status')]);
    }

    public function rules(): array
    {
        return [
            'parent_id' => 'integer|exists:health_plan,id',
            'status' => new Enum(HealthPlanRateSheetStatusEnum::class),
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.exists' => 'Plan does not exist',
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
