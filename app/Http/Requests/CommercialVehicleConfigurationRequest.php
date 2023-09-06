<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CommercialVehicleConfigurationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user() ? true : false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'car_make_id' => [
                'required',
                'integer',
                Rule::exists('car_make', 'id'),
            ],
            'car_model_id' => [
                'required',
                'array',
                'min:1',
            ],
            'car_mode_id.*' => [
                'required',
                'integer',
                Rule::exists('car_model', 'id'),

            ],

        ];
    }
}
