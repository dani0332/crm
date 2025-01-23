<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatusEnum;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

class DeleteQuoteDocumentRequest extends FormRequest
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
        return [
            'quote_uuid' => 'required',
            'doc_name' => 'required',
            'doc_uuid' => 'required|exists:quote_documents,doc_uuid',
        ];
    }

    /**
     * validate quote record and maximum number of alread uploaded files
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // check for quote records if exists
            if (! $quote = $this->getQuoteObject(request()->quoteType, request()->quote_uuid)) {
                $validator->errors()->add('type', 'Invalid quote type or uuid provided');
            }

            // validate if payment is authorized
            if (isset($quote->payment_status_id) && $quote->payment_status_id != PaymentStatusEnum::AUTHORISED) {
                $validator->errors()->add('type', 'Documents can be deleted once payment is authorized.');
            }
        });
    }
}
