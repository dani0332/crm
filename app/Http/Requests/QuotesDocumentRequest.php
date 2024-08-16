<?php

namespace App\Http\Requests;

use App\Enums\quoteTypeCode;
use App\Models\DocumentType;
use App\Enums\LeadSourceEnum;
use App\Models\SendUpdateLog;
use App\Rules\CustomFileType;
use App\Enums\PermissionsEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\PaymentStatusEnum;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidateBase64;

class QuotesDocumentRequest extends FormRequest
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
            'file' => ['required', 'file'],
            'document_type_code' => 'required|exists:document_types,code,is_active,1',
        ];

        if (! empty(request()->document_type_code) && ($this->documentType = DocumentType::where('code', request()->document_type_code)->where('quote_type_id', request()->quote_type_id ?? 0)->first())) {
            $rules['file'][] = new CustomFileType($this->documentType->accepted_files);
            $rules['file'][] = 'max:'.$this->documentType->max_size * 1024;
        }
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
     * @return void
     */
    public function withValidator($validator)
    {

        $validator->after(function ($validator) {
            if (request()->filled('quote_id')) {
                $uploadedDocuments = 0;
                if (request()->is_send_update) {
                    $whereFilter = ['document_type_code' => request()->document_type_code];
                    $quoteDocuments = SendUpdateLog::where('id', request()->send_update_id ?? '')->first();
                    $uploadedDocuments = $quoteDocuments?->documents()->where($whereFilter)->count();

                    if (request()->document_type_code == DocumentTypeCode::SEND_UPDATE_AUDIT_RECORD) {
                        if (! auth()->user()->can(PermissionsEnum::AUDITDOCUMENT_UPLOAD)) {
                            $validator->errors()->add('error', 'This section is for audit purposes only. Only authorised users can upload files here');
                        }
                    }

                } else {
                    $quote = $this->getQuoteObjectBy(request()->folder_path ?? '', request()->quote_id, 'uuid');
                    /**
                     * documents can be attached to a member for health quote type
                     */
                    if (in_array(ucfirst(request()->quoteType), [quoteTypeCode::Health, quoteTypeCode::Travel]) && isset($quote->id) && ! empty(request()->member_detail_id)) {
                        //check for quote records if exists
                        $memberExists = $quote->customerMembers()->where('id', request('member_detail_id'))->exists();
                        if (!$memberExists) {
                            $validator->errors()->add('member_detail_id', 'Invalid member detail id provided');
                        }
                    }

                    if (! in_array(ucfirst(request()->quoteType), [quoteTypeCode::Health, quoteTypeCode::Travel]) && ! empty(request()->member_detail_id)) {
                        $validator->errors()->add('member_detail_id', 'Member can be attached only for Health Insurance type');
                    }

                    $quote_source = data_get($quote, 'source', '');
                    if ($quote_source == LeadSourceEnum::DUBAI_NOW) {
                        //validate if payment is authorized capture or partial capture
                        if (isset($quote->payment_status_id) && ! in_array($quote->payment_status_id, [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])) {
                            $validator->errors()->add('type', 'Documents can be uploaded once payment is authorized, captured or partial captured.');
                        }
                    } else {
                        //validate if payment is authorized
                        if (request()->quoteType != strtolower(quoteTypeCode::Travel)) {
                            if(isset($quote->payment_status_id) && $quote->payment_status_id != PaymentStatusEnum::AUTHORISED) {
                                $validator->errors()->add('type', 'Documents can be uploaded once payment is authorized.');
                            }
                        }
                    }

                    //check for maximum number of files uploaded against selected quote and document type
                    if ($this->documentType && $quote && $quote->documents->where('document_type_code', request()->document_type_code)->count() >= $this->documentType->max_files) {
                        $validator->errors()->add('file', 'You can only upload a maximum of '.$this->documentType->max_files.' files');
                    }



                    if(!empty($quote) ){
                        $uploadedDocuments = $quote->documents->where('document_type_code', request()->document_type_code)->count();
                    }

                }
                //check for maximum number of files uploaded against selected quote and document type
                if ($this->documentType && ($uploadedDocuments >= $this->documentType->max_files)) {
                    $validator->errors()->add('error', 'You can only upload a maximum of '.$this->documentType->max_files.' files for ( '.$this->documentType->text.' )');
                }
            }
        });
    }
}
