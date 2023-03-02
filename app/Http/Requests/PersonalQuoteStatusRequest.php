<?php

namespace App\Http\Requests;

use App\Enums\QuoteStatusEnum;
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
            if ($data['quote_status_id'] == QuoteStatusEnum::TransactionApproved) {
                $rules['transapp_code'] = 'required';
            }

            if ($data['quote_status_id'] == QuoteStatusEnum::Lost) {
                $rules['lost_reason_id'] = 'required';
            }
        }

        return $rules;
    }
}
