<?php

namespace App\Http\Requests;

use App\Repositories\CustomerRepository;
use App\Repositories\PersonalQuoteRepository;
use Illuminate\Foundation\Http\FormRequest;
use Psy\Util\Str;

class CustomerAdditionalContactRequest extends FormRequest
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
            'key' => 'required',
            'value' => 'required',
            'quote_id' => 'required'
        ];
    }

    /**
     * validate quote record and maximum number of alread uploaded files
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $customer = CustomerRepository::where('id', request()->customer_id)->with(['additionalContactInfo' => function($q){
                $q->where('key', request()->key)->where('value', request()->value);
            }])->first();

            $quote = PersonalQuoteRepository::where('id', request()->quote_id)->first();

            /**
             * check if email/mobile already exists in customer, quote or additional contact info
             */
            if($customer->{request()->key} == request()->value
                || count($customer->additionalContactInfo) > 0
                || $quote->{request()->key} == request()->value
            ) {
                $validator->errors()->add('value', ucfirst(str_replace('_', ' ', request()->key)) . ' is already in use for a customer. Please try another.' );
            }

        });
    }
}
