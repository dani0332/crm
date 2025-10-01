<?php

namespace App\Http\Requests;

use App\Enums\GenericRequestEnum;
use Illuminate\Foundation\Http\FormRequest;

class YachtQuoteRequest extends FormRequest
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
            'first_name' => 'required|between:1,20',
            'last_name' => 'required|between:1,50',
            'email' => 'required|email:rfc,dns',
            'mobile_no' => 'required',
            'company_name' => 'max:250',
            'company_address' => 'max:1000',
            'boat_details' => 'required|max:1000',
            'engine_details' => 'required|max:2000',
            'claim_experience' => 'required|max:1000',
            'asset_value' => 'required|numeric',
            'use' => 'required|max:1000',
            'operator_experience' => 'required|max:1000',
            'dob' => 'nullable|before:today',
            'gender' => 'nullable|string|in:'.GenericRequestEnum::MALE_SINGLE.','.GenericRequestEnum::FEMALE.'',
            'nationality_id' => 'nullable|exists:nationality,id',
            'sub_source_id' => 'nullable|exists:lookups,id',
            'sub_source_options_id' => 'nullable|exists:lookups,id',
            'primary_ref_id' => 'nullable|string|max:255',
            'partner_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}
