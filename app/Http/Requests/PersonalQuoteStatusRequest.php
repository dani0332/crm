<?php

namespace App\Http\Requests;

use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Models\Customer;
use App\Models\PersonalQuote;
use Illuminate\Foundation\Http\FormRequest;

class PersonalQuoteStatusRequest extends FormRequest
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
            'quote_status_id' => 'required',
            'notes' => 'nullable',
        ];

        if (! empty($data['quote_status_id'])) {
            if ($data['quote_status_id'] == QuoteStatusEnum::Lost) {
                $rules['lost_reason_id'] = 'required';
            }
        }

        return $rules;
    }

    /**
     * validate quote record and maximum number of alread uploaded files
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $quoteObject = PersonalQuote::where('uuid', request()->quote_uuid)->firstOrFail();

            $customerProfileDetails = Customer::where('id', $quoteObject->customer_id)->first([
                'insured_first_name',
                'insured_last_name',
                'emirates_id_number',
                'emirates_id_expiry_date',
            ])->toArray();

            if (in_array(null, $customerProfileDetails) && request()->quote_status_id == QuoteStatusEnum::TransactionApproved) {
                $validator->errors()->add('value', 'Please update customer profile information before moving to '.quoteStatusCode::TRANSACTIONAPPROVED.' status');
            }

        });
    }
}
