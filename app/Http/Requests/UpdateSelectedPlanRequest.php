<?php

namespace App\Http\Requests;

use App\Enums\QuoteTypes;
use App\Models\HealthQuote;
use App\Rules\ValidateAuthorizedPayment;
use App\Services\HealthQuoteService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSelectedPlanRequest extends FormRequest
{
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $quoteType = strtolower(request()->quoteType);

        $rules = [
            'plan_id' => 'required',
        ];

        if ($quoteType == strtolower(QuoteTypes::HEALTH->value)) {
            $rules['copay_id'] = 'required';
        }

        if ($quoteType == strtolower(QuoteTypes::TRAVEL->value)) {
            $rules['selected_plan_id'] = 'sometimes';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $code = request()->code;

        $validator->after(function ($validator) use ($code) {
            $rule = new ValidateAuthorizedPayment($code);
            $rule->validate($validator, $code);

            if (strtolower(request()->quoteType) == strtolower(QuoteTypes::HEALTH->value)) {
                $quote = HealthQuote::where('code', request()->code)->with(['payments.paymentSplits'])->first();
                if (! $quote) {
                    $validator->errors()->add('error', 'Quote not found');

                    return;
                }
                $payments = $quote->payments;

                if ($quote?->is_quote_locked && ! app(HealthQuoteService::class)->canBypassPlanLock($quote, $payments)) {
                    $validator->errors()->add('error', 'Edits are not permitted once the lead has reached Transaction Approved status');
                }
            }
        });
    }
}
