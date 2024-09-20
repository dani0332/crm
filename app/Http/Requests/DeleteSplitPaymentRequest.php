<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\GenericQueriesAllLobs;

class DeleteSplitPaymentRequest extends FormRequest
{
    use GenericQueriesAllLobs;
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
        return [
            'model_type' => 'required|string',
            'quote_id' => 'required|integer',
            'payment_split_id' => 'required|integer|exists:payment_splits,id',
        ];
    }

    /**
     * validate quote record 
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quoteModel = $this->getQuoteObject(request()->model_type, request()->quote_id);
            if (! $quoteModel) {
                $validator->errors()->add('value', 'Quote Not Exists');
            }
        });

    }
}
