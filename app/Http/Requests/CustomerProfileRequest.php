<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class CustomerProfileRequest extends FormRequest
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
            'customer_id' => 'required|int',
            'insured_first_name' => 'nullable',
            'insured_last_name' => 'nullable',
            'emirates_id_number'=> 'nullable',
            'emirates_id_expiry_date' => 'nullable|after_or_equal:today|date_format:Y-m-d'
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'emirates_id_expiry_date' => isset($this->emirates_id_expiry_date) ? Carbon::parse($this->emirates_id_expiry_date)->format('Y-m-d') : null,
        ]);
    }

    /**
     * Get the validation rule messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'customer_id.required' => 'Something went wrong. Customer not associated with this lead',
        ];
    }
}
