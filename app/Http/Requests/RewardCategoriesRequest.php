<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RewardCategoriesRequest extends FormRequest
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
        ];
    }
}
