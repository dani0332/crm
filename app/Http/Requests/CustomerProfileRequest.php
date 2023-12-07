<?php

namespace App\Http\Requests;

use App\Enums\CustomerTypeEnum;
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
        $rules = [];
        if ($this->customer_type == CustomerTypeEnum::Individual) {
            $rules = array_merge($rules, [
                'customer_id' => 'required|int',
                'insured_first_name' => 'required|max:200',
                'insured_last_name' => 'required|max:200',
                'emirates_id_number' => 'required',
                'emirates_id_expiry_date' => 'required|after_or_equal:today|date_format:Y-m-d',
            ]);
        }

        if ($this->customer_type == CustomerTypeEnum::Entity) {
            $rules = array_merge($rules, [
                'trade_license_no' => 'required|max:200',
                'company_name' => 'required|max:200',
                'company_address' => 'required',
                'industry_type_code' => 'nullable',
                'emirate_of_registration_id' => 'nullable',
            ]);
        }

        return $rules;
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
