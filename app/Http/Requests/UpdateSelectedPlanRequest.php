<?php

namespace App\Http\Requests;

use App\Enums\QuoteTypes;
use App\Rules\ValidateAuthorizedPayment;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\HealthQuote;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
                $quote = HealthQuote::where('code', request()->code)->first();
                if ($quote?->is_quote_locked) {
                    $validator->errors()->add('error', 'Edits are not permitted once the lead has reached Transaction Approved status');
                }
            }
        });
    }
}
