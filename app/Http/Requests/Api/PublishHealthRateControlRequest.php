<?php

namespace App\Http\Requests\Api;

use App\Rules\HealthRateControlPublishableRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PublishHealthRateControlRequest extends FormRequest
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
                new HealthRateControlPublishableRule,
            ],
            'user_id' => 'bail|required|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id.integer' => 'Id must be an integer',
            'user_id.exists' => 'Publish by user not found',
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
