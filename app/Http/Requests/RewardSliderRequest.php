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
        switch ($this->method()) {
            case 'POST':
                return [
                    'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                    'link' => 'required|max:1000',
                    'sort_order' => 'required|integer',
                ];
                break;
            case 'PUT':
                return [
                    'image' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                    'link' => 'required|max:1000',
                    'sort_order' => 'required|integer',
                ];
            default:
                break;
        }

    }
}
