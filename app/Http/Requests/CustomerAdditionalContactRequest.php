<?php

namespace App\Http\Requests;

use App\Enums\GenericRequestEnum;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

class CustomerAdditionalContactRequest extends FormRequest
{
    use GenericQueriesAllLobs;
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
        $rules = [
            'quote_id' => 'required',
            'value' => 'required',
            'key' => 'required',
        ];

        if (request()->segment(1) == 'customer-additional-contact') {
            $rules['customer_id'] = 'required';
            $rules['quote_type'] = 'nullable';
        }

        if ($this->key == GenericRequestEnum::EMAIL) {
            $rules['value'] = 'required|email:rfc,dns';
        }

        return $rules;
    }

    /**
     * validate quote record and maximum number of alread uploaded files
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quote = $this->getQuoteObject(request()->quote_type, request()->quote_id);
            /**
             * check if email/mobile already exists in customer, quote or additional contact info
             */
            if ($quote->{request()->key} == request()->value) {
                $validator->errors()->add('value', ucfirst(str_replace('_', ' ', request()->key)).' is already in use for a current lead. Please try another.');
            }
        });
    }
}
