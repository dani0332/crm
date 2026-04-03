<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GroupNationalitiesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'group_ids' => 'required|array',
        ];
    }
}
