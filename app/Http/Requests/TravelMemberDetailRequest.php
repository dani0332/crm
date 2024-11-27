<?php

namespace App\Http\Requests;

use App\Enums\QuoteTypes;
use Illuminate\Foundation\Http\FormRequest;

class TravelMemberDetailRequest extends FormRequest
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
            'travel_quote_request_id' => 'required',
            'first_name' => 'sometimes|required',
            'name' => 'sometimes|required',
            'nationality_id' => 'nullable',
            'dob' => 'required',
            'relation_code' => 'nullable',
            'quote_request_id' => 'sometimes|required',
            'customer_id' => 'required',
            'uae_resident' => 'nullable',
            'emirates_id_number' => 'nullable',
            'passport' => 'nullable',
            'gender' => 'nullable',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quoteType = request()->quote_type;
            $name = request()->name;
            $pattern = "/^\b\w+\b\s+\b\w+\b/";
            if (ucwords($quoteType) == QuoteTypes::TRAVEL->value && ! preg_match($pattern, $name)) {
                // regix to ensures that the first_name contains at least two words separated by whitespace
                $validator->errors()->add('name', 'The member name must contain at least two words.');
            }
        });
    }
}
