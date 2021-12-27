<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BaseDiscountRequest extends FormRequest
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
            'value_start' => 'required|numeric|min:0',
            'value_end' => 'nullable|numeric|min:0',
            'vehicle_type_id' => 'required|numeric',
            'comprehensive_discount' => 'required|numeric|min:0',
            'agency_discount' => 'required|numeric|min:0',
        ];
    }
}
