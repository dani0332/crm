<?php

namespace App\Http\Requests;

use App\Models\PaymentSplits;
use App\Models\SendUpdateLog;
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
            'paymentSplitId' => 'required',
            'quoteRequestId' => 'required',
            'quoteType' => 'required',
            'sendUpdateId' => 'nullable',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! (new SageApiService)->isSageEnabled()) {
                $validator->errors()->add('sage', 'Sage is not enabled.');
            }
            $paymentSplit = PaymentSplits::select(['id'])->whereId(request()->paymentSplitId)->first();
            if (! $paymentSplit) {
                $validator->errors()->add('payment_split', 'Payment split not found.');
            }
            $sendUpdateLog = SendUpdateLog::select(['id'])->whereId(request()->sendUpdateId)->first();
            if (request()->sendUpdateId && ! $sendUpdateLog) {
                $validator->errors()->add('send_update', 'Send Update not found.');
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
