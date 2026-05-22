<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FetchSavingsProviderPlanRequest extends FormRequest
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
     * Whitelist matches the KEN /fetch-savings-provider-plan payload built in useSavingsPlans.js.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'planId' => ['required', 'integer'],
            'providerCode' => ['required', 'string', 'max:64'],
            'isIndividualLoading' => ['required', 'boolean'],
            'lang' => ['required', 'string', 'max:16'],
            'planData' => ['required', 'array'],
            'planData.investmentAmount' => ['required', 'numeric', 'min:0'],
            'planData.currency' => ['required', 'string', 'max:16'],
            'planData.currencyId' => ['nullable', 'integer'],
            'planData.paymentTerm' => ['required', 'integer', 'min:0'],
            'planData.tenure' => ['nullable', 'string'],
            'planData.tenureId' => ['nullable', 'integer'],
            'planData.investmentFrequency' => ['required', 'string', 'max:255'],
            'planData.investmentFrequencyId' => ['nullable', 'integer'],
            // Forwarded as-is to KEN; only ensure it is an array when provided.
            'planData.riders' => ['sometimes', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'planId.required' => 'The plan id is required.',
            'providerCode.required' => 'The provider code is required.',
            'planData.required' => 'The plan data is required.',
        ];
    }
}
