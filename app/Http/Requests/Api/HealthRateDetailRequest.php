<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class HealthRateDetailRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }
    public function rules(): array
    {
        return [
            'id' => ['integer', 'exists:health_rates,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.integer' => 'Id must be an integer',
            'id.exists' => 'Invalid health rate id',
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
