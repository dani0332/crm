<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RewardSliderRequest extends FormRequest
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
                'image' => ($this->method() == 'POST' ? 'required|': '') . 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'link' => ($this->method() == 'POST' ? 'required|': '') .'max:1000',
                'sort_order' => ($this->method() == 'POST' ? 'required|': '') .'integer'
        ];
    }
}
