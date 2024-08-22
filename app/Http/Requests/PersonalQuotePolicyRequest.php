<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonalQuotePolicyRequest extends FormRequest
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
            'policy_number' => 'required',
            'policy_issuance_date' => 'required',
            'policy_start_date' => 'required',
            'policy_expiry_date' => 'required',
            'premium' => 'required|numeric',
        ];
    }
}
