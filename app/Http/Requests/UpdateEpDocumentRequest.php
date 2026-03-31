<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEpDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'epId' => ['required', 'exists:embedded_products,id'],
            'modelType' => ['required', 'string'],
            'quoteId' => ['required', 'integer'],
            'documentId' => ['required', 'exists:quote_documents,id'],
            'documentNumber' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'remarks' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'epId.required' => 'EP ID is required.',
            'epId.exists' => 'The selected EP does not exist.',
            'modelType.required' => 'Model type is required.',
            'quoteId.required' => 'Quote ID is required.',
            'documentId.required' => 'Document ID is required.',
            'documentId.exists' => 'The selected document does not exist.',
            'documentNumber.required' => 'Document number is required.',
            'file.required' => 'A PDF file is required.',
            'file.mimes' => 'The file must be a PDF.',
            'remarks.required' => 'Remarks are required.',
        ];
    }
}
