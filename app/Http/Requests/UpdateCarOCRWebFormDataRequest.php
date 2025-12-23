<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCarOCRWebFormDataRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'quoteId' => $this->route('quoteId'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quoteId' => 'required|exists:car_quote_request,id',
        ];
    }
}
