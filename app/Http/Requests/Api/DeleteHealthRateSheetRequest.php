<?php

namespace App\Http\Requests\Api;

use App\Rules\HealthRateSheetStatusValidRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeleteHealthRateSheetRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function rules(): array
    {
        return [
            'id' => ['bail', 'integer', 'exists:health_rates_control,id', new HealthRateSheetStatusValidRule('Only draft or scheduled rate sheets can be deleted')],
        ];
    }

    public function messages(): array
    {
        return [
            'id.exists' => 'Rate sheet does not exist',
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
