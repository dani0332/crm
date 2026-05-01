<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserActiveStateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required|exists:users,id',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'id.exists' => 'User not found',
            'status.boolean' => 'Status must be a boolean',
        ];
    }
}
