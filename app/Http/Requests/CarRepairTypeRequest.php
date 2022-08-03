<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CarRepairTypeRequest extends FormRequest
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
            'text' => 'required|max:120',
            'text_ar' => 'required|max:120',
            'sort_order' => 'required',
            'is_active' => 'nullable',
        ];
    }

    /**
     * Get the validation rule messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'text.required' => 'Text En is required',
            'text_ar.required' => 'Text Ar is required',
            'text.max' => 'Text En max length is 120',
            'text_ar.max' => 'Text Ar max length is 120',
        ];
    }
}
