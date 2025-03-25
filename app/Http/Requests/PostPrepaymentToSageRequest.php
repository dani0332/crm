<?php

namespace App\Http\Requests;

use App\Models\PaymentSplits;
use App\Services\SageApiService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

class PostPrepaymentToSageRequest extends FormRequest
{
    use GenericQueriesAllLobs;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        return [
            'paymentSplit' => 'required',
            'quoteRequestId' => 'required',
            'quoteType' => 'required',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! (new SageApiService)->isSageEnabled()) {
                $validator->errors()->add('sage', 'Sage is not enabled.');
            }
            $paymentSplit = PaymentSplits::whereId(request()->paymentSplit)->first();
            if (! $paymentSplit) {
                $validator->errors()->add('payment_split', 'Payment split not found.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_split.required' => 'Payment split ID is required.',
            'payment_split.exists' => 'The selected payment split does not exist.',
        ];
    }
}
