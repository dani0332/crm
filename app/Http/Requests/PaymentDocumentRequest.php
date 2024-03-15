<?php

namespace App\Http\Requests;

use App\Models\DocumentType;
use Illuminate\Foundation\Http\FormRequest;

class PaymentDocumentRequest extends FormRequest
{
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
            'file' => 'required|array',
            'quote_type_id' => 'required|exists:document_types,quote_type_id',
            'folder_path' => 'required|exists:document_types,folder_path',
            'document_type_code' => 'required|exists:document_types,code,is_active,1',
        ];
        // verify document type options
        if (! empty(request()->document_type_code) && ($this->documentType = DocumentType::where('code', request()->document_type_code)->where('quote_type_id', request()->quote_type_id ?? 0)->first())) {
            $rules['file.*.*'] = 'file|mimes:'.(str_replace('.', '', $this->documentType->accepted_files)).'|max:'.($this->documentType->max_size * 1024);
        }

        return $rules;
    }
}
