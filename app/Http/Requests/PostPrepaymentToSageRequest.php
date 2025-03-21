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
            'paymentSplit' => 'required|exists:payment_splits,id',
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

            /*$quote = $this->getQuoteObject(request()->quoteType, request()->quoteRequestId);
            $paymentSplit = PaymentSplits::whereId(request()->paymentSplit)->first();

            $response = (new SageApiService)->preChecksForPostPrepaymentSchedule($quote, $paymentSplit);

            if (! $response['status']) {
                foreach ($response['error'] as $key => $error) {
                    $validator->errors()->add($key, $error);
                }
            }*/
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
