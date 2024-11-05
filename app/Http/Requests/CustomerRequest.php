<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
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
            'first_name' => 'required|max:120',
            'last_name' => 'required|max:120',
            'email' => 'required|email:rfc,dns|max:150',
            'mobile_no' => 'nullable',
            'lang' => 'nullable|string',
            'gender' => 'nullable|string',
            'dob' => 'nullable',
            'nationality_id' => 'nullable|integer',
            'has_alfred_access' => 'nullable|boolean',
            'has_reward_access' => 'nullable|boolean',
            'receive_marketing_updates' => 'nullable|boolean',
        ];
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_alfred_access' => filter_var($this->has_alfred_access, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'has_reward_access' => filter_var($this->has_reward_access, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }
}
