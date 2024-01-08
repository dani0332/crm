<?php

namespace App\Http\Requests;

use App\Enums\QuoteStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class DragAndDropUpdateLeadStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'data.form' => 'required',
            'data.form.id' => 'required',
            'data.form.quote_status_id'  => 'required',
            'data.to' => 'required',
            'data.to.quote_status_id'  => 'required',
        ];

        return $rules;
    }

    /**
     * validate quote record and maximum number of alread uploaded files
     */
    public function withValidator($validator)
    {
        // Lost reason required if quote status is lost
        $validator->after(function ($validator) {
            $data = $this->all();
            $form = $data['data']['form'];
            $to = $data['data']['to'];

            if ($form['quote_status_id'] == $to['quote_status_id']) {
                $validator->errors()->add('value', 'Quote status is same as previous status.');
            }

            if(in_array($to['quote_status_id'], [QuoteStatusEnum::TransactionApproved, quoteStatusEnum::PolicyIssued])) {
                $validator->errors()->add('value', 'Transaction approval required to verify that all necessary information and documentation are in place');
            }


        });
    }
}
