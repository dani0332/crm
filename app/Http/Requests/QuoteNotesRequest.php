<?php

namespace App\Http\Requests;

use App\Models\DocumentType;
use Illuminate\Foundation\Http\FormRequest;

class QuoteNotesRequest extends FormRequest
{
    protected $documentType;

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
        $this->documentType = DocumentType::where('code', request()->document_type_code)->first();

        $rules = [
            'quoteRequestId' => 'required|integer',
            'quote_uuid' => 'required|string',
            'quoteType' => 'required|string',
            'quoteStatusId' => 'required|integer',
            'document_type_code' => 'required|string',
            'notes' => 'required|string',
        ];

        if (request()->hasFile('files')) {
            foreach (request()->file('files') as $key => $file) {
                $rules['files.'.$key] = 'mimes:'.(str_replace('.', '', $this->documentType->accepted_files)).'|max:'.($this->documentType->max_size * 1024);
            }
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'files.*' => 'The file(s) must be a file of type:'.str_replace('.', '', $this->documentType->accepted_files).', allowed max size:'.$this->documentType->max_size.' MB',
        ];
    }
}
