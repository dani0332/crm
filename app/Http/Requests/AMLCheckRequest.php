<?php

namespace App\Http\Requests;

use App\Enums\CustomerTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class AMLCheckRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];
        if ($this->customer_type == CustomerTypeEnum::Individual) {
            $rules = [
                'nationality_id' => 'required',
                'dob' => 'required',
                'insured_first_name' => 'required|max:200',
                'insured_last_name' => 'required|max:200',
            ];
        }

        if ($this->customer_type == CustomerTypeEnum::Entity) {
            $rules = [
                'trade_license_no' => 'required|max:200',
                'company_name' => 'required|max:200',
                'company_address' => 'required',
                'entity_type_code' => 'nullable',
                'industry_type_code' => 'nullable',
                'emirate_of_registration_id' => 'nullable',
            ];
        }

        return $rules;

    }
}
