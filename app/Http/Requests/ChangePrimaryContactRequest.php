<?php

namespace App\Http\Requests;

use App\Enums\GenericRequestEnum;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

class ChangePrimaryContactRequest extends FormRequest
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
            'key' => 'required|in:'.GenericRequestEnum::EMAIL.','.GenericRequestEnum::MOBILE_NO,
            'value' => 'required',
            'quote_id' => 'required',
            'quote_customer_id' => 'nullable',
            'quote_primary_email_address' => 'nullable',
            'quote_primary_mobile_no' => 'nullable',
        ];

        if (request()->segment(1) == 'customer-additional-contact') {
            $rules['quote_type'] = 'required';
        }

        return $rules;
    }

}
