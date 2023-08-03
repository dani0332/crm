<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonalQuotePaymentRequest extends FormRequest
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
        $data = request()->all();

        $rules = [
            'collection_type' => 'required',
            'captured_amount' => 'required|numeric',
            'payment_methods_code' => 'required',
            'insurance_provider_id' => 'required|int|exists:insurance_provider,id',
        ];

        return $rules;
    }
}
