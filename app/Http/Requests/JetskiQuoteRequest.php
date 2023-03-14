<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JetskiQuoteRequest extends FormRequest
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
            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',
            'email' => 'required|email:rfc,dns',
            'mobile_no' => 'required',
            'jetski_make' => 'required|max:50',
            'jetski_model' => 'required|max:50',
            'year_of_manufacture_id' => 'required',
            'max_speed' => 'required|integer',
            'seat_capacity' => 'required|max:50',
            'engine_power' => 'required|max:50',
            'jetski_material_id' => 'required|exists:lookups,id',
            'jetski_use_id' => 'required|exists:lookups,id',
            'claim_history' => 'required|max:50'
        ];
    }
}
