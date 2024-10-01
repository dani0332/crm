<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivityGetApiRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'entityUId' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'entityUId.required' => 'Entity UUID Required',
            'entityUId.string' => 'Entity UUID Invalid',
        ];
    }
}
