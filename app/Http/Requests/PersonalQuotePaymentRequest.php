<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethodsEnum;
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

        $rules =  [
            'collection_type' => 'required',
            'captured_amount' => 'required|numeric',
            'payment_methods_code' => 'required',
            'insurance_provider_id' => 'required|int|exists:insurance_provider,id',
            'plan_id' => 'required|int|exists:personal_plans,id',
            'reference' => 'nullable'
        ];

        if(!empty($data['payment_method_code']) && $data['payment_method_code'] != PaymentMethodsEnum::CreditCard) {
            $rules['reference'] = 'required';
        }

        return $rules;
    }
}
