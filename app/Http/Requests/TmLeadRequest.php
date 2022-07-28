<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TmLeadRequest extends FormRequest
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
            'customer_name' => 'required|max:50',
            'phone_number' => ['required', 'max:20'],
            'email_address' => 'required|email|max:50',
            'tm_insurance_types_id' => 'required',
            'enquiry_date' => 'required',
            'allocation_date' => 'required',
            'tm_lead_types_id' => 'required',
            'dob' => 'nullable|date_format:Y-m-d',
        ];
    }
}
