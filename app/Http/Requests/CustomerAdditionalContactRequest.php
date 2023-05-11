<?php

namespace App\Http\Requests;

use App\Enums\GenericRequestEnum;
use App\Enums\quoteTypeCode;
use App\Repositories\AdditionalContactRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\PersonalQuoteRepository;
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
        $rules['quote_id'] = 'required';
        $rules['value'] = 'required';
        $rules['key'] = 'required';

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
            $customer = CustomerRepository::where(request()->key, request()->value)->first();
            $additionalContacts = AdditionalContactRepository::where([['key', request()->key], ['value', request()->value]])->first();

            $genericLobs = [quoteTypeCode::Life, quoteTypeCode::Travel];
            if (in_array(request()->quote_type, $genericLobs)) {
                $quote = $this->getQuoteObject(request()->quote_type, request()->quote_id);
            } else {
                $quote = PersonalQuoteRepository::where('id', request()->quote_id)->first();
            }
            /**
             * check if email/mobile already exists in customer, quote or additional contact info
             */
            if ($customer || $additionalContacts || $quote->{request()->key} == request()->value) {
                $validator->errors()->add('value', ucfirst(str_replace('_', ' ', request()->key)).' is already in use for a customer. Please try another.');
            }
        });
    }
}
