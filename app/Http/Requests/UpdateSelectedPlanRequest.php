<?php

namespace App\Http\Requests;

use App\Enums\QuoteTypes;
use App\Services\SplitPaymentService;
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
            app(SplitPaymentService::class)->validateAuthorizedPayment($validator, $code);
        });
    }
}
