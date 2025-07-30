<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RetryPrepaymentRequest extends FormRequest
{
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
            'paymentCode' => 'required',
            'srNo' => 'required',
            'sendUpdateId' => 'nullable',
        ];
    }
}
