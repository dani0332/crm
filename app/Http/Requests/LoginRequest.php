<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => Rule::in([
                'qa_automation@myalfred.com',
                'qa_automation1@myalfred.com',
                'qa_automation2@myalfred.com',
                'qa_automation3@myalfred.com',
                'qa_automation4@myalfred.com',
                'qa_automation5@myalfred.com',
            ]),
            'password' => 'required',
        ];
    }
}
