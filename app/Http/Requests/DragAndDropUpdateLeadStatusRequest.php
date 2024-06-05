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
            'data.form.id' => 'required',
            'data.form.quote_status_id' => 'required',
            'data.form.quoteTypeId' => 'required',
            'data.to.quote_status_id' => 'required',
        ];

        if (request()->get('data')['to']['quote_status_id'] == QuoteStatusEnum::Lost) {
            $rules['data.to.lost_reason'] = 'required';
        }

        return $rules;
    }

    /**
     * validate quote record and maximum number of alread uploaded files
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if (in_array(request()->get('data')['to']['quote_status_id'], [QuoteStatusEnum::TransactionApproved, quoteStatusEnum::PolicyIssued])) {
                $validator->errors()->add('value', 'Transaction approval is required');
            }

            // if(request()->get('data.to.quote_status_id') == request()->get('data.form.quote_status_id')) {
            //     $validator->errors()->add('value', 'Quote status is same as previous status.');
            // }

        });
    }
}
