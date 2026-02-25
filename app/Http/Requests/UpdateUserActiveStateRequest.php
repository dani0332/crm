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
}
