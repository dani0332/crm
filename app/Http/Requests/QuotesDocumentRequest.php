<?php

namespace App\Http\Requests;

use App\Models\DocumentType;
use App\Models\SendUpdateLog;
use App\Rules\CustomFileType;
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
            'file' => ['required', 'file'],
            'document_type_code' => 'required|exists:document_types,code,is_active,1',
        ];

        if (! empty(request()->document_type_code) && ($this->documentType = DocumentType::where('code', request()->document_type_code)->where('quote_type_id', request()->quote_type_id ?? 0)->first())) {
            $rules['file'][] = new CustomFileType($this->documentType->accepted_files);
            $rules['file'][] = 'max:'.$this->documentType->max_size * 1024;
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
                } else {
                    $quote = $this->getQuoteObject(request()->folder_path ?? '', request()->quote_id);
                    $uploadedDocuments = $quote->documents->where('document_type_code', request()->document_type_code)->count();
                }
                //check for maximum number of files uploaded against selected quote and document type
                if ($this->documentType && ($uploadedDocuments >= $this->documentType->max_files)) {
                    $validator->errors()->add('error', 'You can only upload a maximum of '.$this->documentType->max_files.' files for ( '.$this->documentType->text.' )');
                }
            }
        });
    }
}
