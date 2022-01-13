<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RewardRequest extends FormRequest
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
            'coupon_code' => 'max:25',
            'partner_id' => 'required',
            'discount' => 'required|string|max:15',
            'start_date' => 'required',
            'end_date' => 'required',
            'reward_categories' => 'required',
            'reward_tags' => 'required'
        ];
    }
}
