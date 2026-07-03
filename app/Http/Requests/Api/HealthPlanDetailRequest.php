<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class HealthPlanDetailRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $id = collect([
            'id',
            'healthPlanId',
        ])->map(fn ($key) => $this->route($key))
            ->first(fn ($value) => ! is_null($value));

        $this->merge(['id' => $id]);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:health_plan,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Id is required',
            'id.integer' => 'Id must be an integer',
            'id.exists' => 'Invalid health plan id',
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
