<?php

namespace App\Http\Requests;

use App\Rules\ValidateQuoteObject;
use Illuminate\Foundation\Http\FormRequest;

class DeleteQuoteDocumentRequest extends FormRequest
{
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
        return [
            'quote_uuid'    => ['required', new ValidateQuoteObject],
            'doc_name'  => 'required|exists:quote_documents,doc_name',
            'doc_uuid'  => 'required|exists:quote_documents,doc_uuid'
        ];
    }
}
