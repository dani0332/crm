<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmbeddedProductRequest extends FormRequest
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
        $id = request()->route()->parameter('product');

        $minAgeRule = 'nullable|int';
        if ($this->input('min_age') && $this->input('max_age')) {
            $minAgeRule = 'nullable|int|lte:max_age';
        }

        $minValueRule = 'nullable|int';
        if ($this->input('min_value') && $this->input('max_value')) {
            $minValueRule = 'nullable|int|lte:max_value';
        }

        return [
            'insurance_provider_id' => 'nullable|int|exists:insurance_provider,id',
            'product_name' => 'required',
            'display_name' => 'required',
            'product_type' => 'required',
            'short_code' => $this->isMethod('put') ? 'nullable|unique:embedded_products,short_code,'.$id : 'required|unique:embedded_products',
            'product_category' => 'required',
            'product_validity' => 'nullable',
            'description' => 'nullable',
            'pricings' => 'required',
            'commission_type' => 'required',
            'commission_value' => 'required',
            'placements' => 'required',
            'pricing_type' => 'required',
            'email_template_ids' => 'nullable',
            'uncheck_message' => 'nullable',
            'logic_description' => 'nullable',
            'company_documents' => 'nullable',
            'is_active' => 'nullable',
            'min_age' => $minAgeRule,
            'max_age' => 'nullable|int',
            'min_value' => $minValueRule,
            'max_value' => 'nullable|int',
        ];
    }
}
