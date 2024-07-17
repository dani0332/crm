<?php

namespace App\Http\Requests;

use App\Models\CcPaymentProcessJob;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

class RetrySplitPaymentRequest extends FormRequest
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
            'payment_process_job_id' => 'required|integer',
        ];
    }

    /**
     * validate quote record and maximum number of alread uploaded files
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quoteModel = $this->getQuoteObject(request()->model_type, request()->quote_id);
            if (! $quoteModel) {
                $validator->errors()->add('value', 'Quote Not Exists');
            }
        });

        $validator->after(function ($validator) {
            $paymentProcessJob = CcPaymentProcessJob::find(request()->payment_process_job_id);
            if (! $paymentProcessJob) {
                $validator->errors()->add('value', 'Payment Process Job Not Exists');
            }
        });
    }
}
