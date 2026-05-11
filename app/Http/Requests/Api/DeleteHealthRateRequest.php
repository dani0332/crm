<?php

namespace App\Http\Requests\Api;

use App\Rules\HealthRateStatusValidRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeleteHealthRateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }
    public function rules(): array
    {
        return [
            'id' => ['bail', 'integer', new HealthRateStatusValidRule('Only draft or scheduled rates can be deleted')],
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
