<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NationalityPoolConfigurationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'effective_from' => 'required|date',
            'canonical_nationality_codes' => 'required|array',
        ];
    }
}
