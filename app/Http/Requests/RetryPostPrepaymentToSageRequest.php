<?php

namespace App\Http\Requests;

use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

class RetryPostPrepaymentToSageRequest extends FormRequest
{
    use GenericQueriesAllLobs;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paymentSplitId' => 'required',
            'quoteRequestId' => 'required',
            'quoteType' => 'required',
            'sendUpdateId' => 'nullable',
        ];
    }
}
