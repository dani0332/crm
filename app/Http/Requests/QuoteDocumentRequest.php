<?php

namespace App\Http\Requests;

use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\quoteTypeCode;
use App\Models\DocumentType;
use App\Rules\ValidateBase64;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

// This validation belongs to API side while we upload document from ECOM side
class QuoteDocumentRequest extends FormRequest
{
    use GenericQueriesAllLobs;

    protected $documentType;

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
            'file' => 'required|file',
            'document_type_code' => 'required|exists:document_types,code,is_active,1',
            'quote_uuid' => 'required',
            'member_detail_id' => 'nullable',
            'is_base_64' => 'nullable',
        ];

        if (! empty(request()->document_type_code) && ($this->documentType = DocumentType::where('code', request()->document_type_code)->first())) {
            if (! (request()->is_base_64)) {
                $rules['file'] = 'mimes:'.(str_replace('.', '', $this->documentType->accepted_files)).'|max:'.($this->documentType->max_size * 1024);
            }
        }

        if (request()->is_base_64) {
            $rules['file'] = ['required', new ValidateBase64($this->documentType)];
        } else {
            $rules['file'] .= '|required|file';
        }

        return $rules;

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

            /**
             * documents can be attached to a member for health quote type
             */
            if (in_array(ucfirst(request()->quoteType), [quoteTypeCode::Health, quoteTypeCode::Travel]) && isset($quote->id) && ! empty(request()->member_detail_id)) {
                // check for quote records if exists
                if (! $quote->customerMembers()->where('id', request()->member_detail_id)->first()) {
                    $validator->errors()->add('member_detail_id', 'Invalid member detail id provided');
                }
            }

            if (! in_array(ucfirst(request()->quoteType), [quoteTypeCode::Health, quoteTypeCode::Travel]) && ! empty(request()->member_detail_id)) {
                $validator->errors()->add('member_detail_id', 'Member can be attached only for Health Insurance type');
            }

            $quote_source = data_get($quote, 'source', '');
            if ($quote_source == LeadSourceEnum::DUBAI_NOW) {
                // validate if payment is authorized capture or partial capture
                if (isset($quote->payment_status_id) && ! in_array($quote->payment_status_id, [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])) {
                    $validator->errors()->add('type', 'Documents can be uploaded once payment is authorized, captured or partial captured.');
                }
            } else {
                // validate if payment is authorized
                if (request()->quoteType != strtolower(quoteTypeCode::Travel)) {
                    if (isset($quote->payment_status_id) && $quote->payment_status_id != PaymentStatusEnum::AUTHORISED) {
                        $validator->errors()->add('type', 'Documents can be uploaded once payment is authorized.');
                    }
                }
            }

            // check for maximum number of files uploaded against selected quote and document type
            if ($this->documentType && $quote && $quote->documents->where('document_type_code', request()->document_type_code)->count() >= $this->documentType->max_files) {
                $validator->errors()->add('file', 'You can only upload a maximum of '.$this->documentType->max_files.' files');
            }
        });
    }
}
