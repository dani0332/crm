<?php

namespace App\Http\Requests;

use App\Models\DocumentType;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

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
            'file' => 'required|file',
            'document_type_code' => 'required|exists:document_types,code,is_active,1',
            'quote_id' => 'required|exists:personal_quotes,id',
            'quote_uuid' => 'required|exists:personal_quotes,code',
        ];

        if (! empty(request()->document_type_code) && ($this->documentType = DocumentType::where('code', request()->document_type_code)->where('quote_type_id', request()->quote_type_id ?? 0)->first())) {
            $rules['file'] .= '|mimes:'.(str_replace('.', '', $this->documentType->accepted_files)).'|max:'.($this->documentType->max_size * 1024);
        }

        return $rules;
    }

    /**
     * @return void
     */
    public function withValidator($validator)
    {
        $quoteId = request()->quoteId ?? request()->quote_id ?? '';
        $validator->after(function ($validator) use ($quoteId) {
            if (! empty($quoteId)) {
                $quote = $this->getQuoteObject(request()->folder_path ?? '', $quoteId);
                //check for maximum number of files uploaded against selected quote and document type
                if ($this->documentType && $quote && $quote->documents->where('document_type_code', request()->document_type_code)->count() >= $this->documentType->max_files) {
                    $validator->errors()->add('error', 'You can only upload a maximum of '.$this->documentType->max_files.' files for ( '.$this->documentType->text.' )');
                }
            }
        });
    }
}
