<?php

namespace App\Http\Requests;

use App\Models\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class QuoteDocumentRequest extends FormRequest
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
        $rules =
        [
            'document'              => 'required|file',//|mimes:xlsm,xlsx,pdf,jpeg,jpg|max:5120
            'document_type_code'    => 'required|exists:document_types,code,is_active,1',
            'uuid'                  => 'required',
        ];

        if(!empty(request()->document_type_code) && ($documentType = DocumentType::where('code', request()->document_type_code)->first()) ) {
            $rules['document'] .= '|mimes:' . (str_replace('.', '', $documentType->accepted_files)) . '|max:' . ($documentType->max_size * 1024) ;
        }

        return  $rules;

    }




    /**
     * check for valid quote model and quote record
     * e.g. first check for valid model class like (CarQuote)
     * and then validate quote record using that model
     * @param $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator)
        {
            $model = '\\App\\Models\\'.ucwords(request()->type).'Quote';

            if( !class_exists($model) ||  (!$quote = $model::where('uuid', @request()->uuid)->first()) ) {
                $validator->errors()->add('type', 'Invalid quote type or uuid provided');
            }

        });
    }

}
