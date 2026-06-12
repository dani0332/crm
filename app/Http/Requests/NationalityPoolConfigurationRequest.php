<?php

namespace App\Http\Requests;

use App\Rules\CheckFutureEffectiveDateNationalityPool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class NationalityPoolConfigurationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'effective_from' => [
                'required',
                'date',
                new CheckFutureEffectiveDateNationalityPool,
            ],
            'canonical_nationality_codes' => 'nullable|array',
            'health_nationality_group_ids' => 'nullable|array',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['error' => $validator->errors()->first()], 422));
    }
}
