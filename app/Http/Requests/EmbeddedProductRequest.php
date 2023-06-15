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
        $id = request()->route()->parameter('embedded_product');

        if ($this->isMethod('put')) {
            return [
                'insurance_provider_id' => 'required|int|exists:insurance_provider,id',
                'product_name' => 'required',
                'short_code' => 'nullable|unique:embedded_products,short_code,'.$id,
                'display_name' => 'required',
                'product_type' => 'required',
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
            ];
        }

        return [
            'insurance_provider_id' => 'required|int|exists:insurance_provider,id',
            'product_name' => 'required',
            'short_code' => 'required|unique:embedded_products',
            'display_name' => 'required',
            'product_type' => 'required',
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
        ];
    }
}
