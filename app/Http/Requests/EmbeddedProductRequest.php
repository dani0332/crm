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
        return [
            'company_name' => 'required',
            'product_name' => 'required',
            'short_code' => 'required',
            'display_name' => 'required',
            'product_type' => 'required',
            'pricing' => 'required',
            'placement' => 'required',
            'description' => 'required',
            'description2' => 'required',
            'commission_type' => 'required',
            'commission_value' => 'required',
            'email_template_id' => 'required',
            'company_documents' => 'required',
          
        ];
    }
}
