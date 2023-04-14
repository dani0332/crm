<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatusEnum;
use App\Models\DocumentType;
use App\Repositories\PersonalQuoteRepository;
use Illuminate\Foundation\Http\FormRequest;

class PersonalQuoteDocumentRequest extends FormRequest
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
            'file' => 'required|file',
            'document_type_code' => 'required|exists:document_types,code,is_active,1',
        ];

        if (! empty(request()->document_type_code) && ($this->documentType = DocumentType::where('code', request()->document_type_code)->first())) {
            $rules['file'] .= '|mimes:'.(str_replace('.', '', $this->documentType->accepted_files)).'|max:'.($this->documentType->max_size * 1024);
        }

        return $rules;
    }

    /**
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! empty(request()->quoteId)) {
                $quote = PersonalQuoteRepository::where('id', request()->quoteId)->firstOrFail();

                //validate if payment is authorized
                if (isset($quote->payment_status_id) && $quote->payment_status_id != PaymentStatusEnum::AUTHORISED) {
                    $validator->errors()->add('error', 'Documents can be uploaded once payment is authorized.');
                }

                //check for maximum number of files uploaded against selected quote and document type
                if ($this->documentType && $quote && $quote->documents->where('document_type_code', request()->document_type_code)->count() >= $this->documentType->max_files) {
                    $validator->errors()->add('error', 'You can only upload a maximum of '.$this->documentType->max_files.' files for ( '.$this->documentType->text.' )');
                }
            }
        });
    }
}
