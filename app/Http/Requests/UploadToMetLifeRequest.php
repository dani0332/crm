<?php

namespace App\Http\Requests;

use App\Enums\QuoteTypes;
use App\Models\DocumentType;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

class UploadToMetLifeRequest extends FormRequest
{
    use GenericQueriesAllLobs;

    protected $documentType;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'quote_uuid' => 'required|string',
            'document_type_code' => 'required|exists:document_types,code,is_active,1',
            'provider_code' => 'required|string|max:10',
            'policy_number' => 'required|string|max:100',
        ];

        if (! empty(request()->document_type_code)) {
            $this->documentType = DocumentType::where('code', request()->document_type_code)->first();
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'quote_uuid.required' => 'Quote UUID is required.',
            'document_type_code.required' => 'Document type code is required.',
            'document_type_code.exists' => 'Invalid document type code provided.',
            'provider_code.required' => 'Provider code is required.',
            'provider_code.max' => 'Provider code cannot exceed 10 characters.',
            'policy_number.required' => 'Policy number is required.',
            'policy_number.max' => 'Policy number cannot exceed 100 characters.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! $quote = $this->getQuoteObject(request()->route('quoteType'), request()->quote_uuid)) {
                $validator->errors()->add('quote_uuid', 'Invalid quote type or UUID provided.');
            }

            if ($this->documentType && $this->documentType->quote_type_id !== QuoteTypes::getIdFromValue(request()->route('quoteType'))) {
                $validator->errors()->add('document_type_code', 'Document type is not compatible with the specified quote type.');
            }
        });
    }

}
