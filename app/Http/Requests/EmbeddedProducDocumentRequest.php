<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmbeddedProducDocumentRequest extends FormRequest
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
            'epId' => 'required|exists:embedded_products,id',
            'modelType' => 'required',
            'quoteId' => 'required',

        ];
    }

    /**
     * @return string[]
     */
    public function messages()
    {
        return [
            'epId.required' => 'EP id is required',
            'modelType.required' => 'Model Type is required',
            'quoteId.required' => 'Quote id is required',
        ];
    }
}
