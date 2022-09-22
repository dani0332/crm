<?php

namespace App\Http\Requests;

use App\Models\DocumentType;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

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
        ];

        if (! empty(request()->document_type_code) && ($this->documentType = DocumentType::where('code', request()->document_type_code)->first())) {
            $rules['file'] .= '|mimes:'.(str_replace('.', '', $this->documentType->accepted_files)).'|max:'.($this->documentType->max_size * 1024);
        }

        return  $rules;
    }

    /**
     * validate quote record and maximum number of alread uploaded files
     *
     * @param $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            //check for quote records if exists
            if ((! $quote = $this->getQuoteObject(request()->quoteType, request()->quote_uuid))) {
                $validator->errors()->add('type', 'Invalid quote type or uuid provided');
            }

            //check for maximum number of files uploaded against selected quote and document type
            if ($this->documentType && $quote && $quote->documents->where('document_type_code', request()->document_type_code)->count() >= $this->documentType->max_files) {
                $validator->errors()->add('file', 'You can only upload a maximum of '.$this->documentType->max_files.' files');
            }
        });
    }
}
