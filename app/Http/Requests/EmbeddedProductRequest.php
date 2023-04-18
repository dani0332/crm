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

        return [
            'insurance_provider_id' => 'required|int|exists:insurance_provider,id',
            'product_name' => 'required',
            'short_code' => 'required|max:3|unique:embedded_products,short_code,'.$id,
            'display_name' => 'required',
            'product_type' => 'required',
            'logic' => 'required',
            'positions' => 'required',
            'pricings' => 'required',
            'description' => 'required',
            'description2' => 'required',
            'commission_type' => 'required',
            'commission_value' => 'required',
            'email_template_id' => 'required',
            'company_documents' => 'required',
            'pricing_type' => 'required',
            'removal_confirmation' => 'required',

        ];
    }
}
