<?php

namespace App\Http\Requests;

use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CustomerPrimaryEmailRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quote_id' => 'required',
            'quote_type' => 'required',
            'key' => 'required',
            'value' => 'required',
            'keep_existing_primary_email' => 'nullable|numeric|in:0,1',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->key === GenericRequestEnum::EMAIL) {
                $quote = $this->getQuoteObject($this->quote_type, $this->quote_id);

                if (! $quote) {
                    $validator->errors()->add('quote_id', 'Invalid quote type or quote ID provided');

                    return;
                }

                if (
                    in_array($quote?->quote_status_id, [
                        QuoteStatusEnum::POLICY_BOOKING_QUEUED,
                        QuoteStatusEnum::POLICY_BOOKING_FAILED,
                    ])
                ) {
                    $validator->errors()->add('error', 'Primary email ID cannot be changed while the policy booking is in progress.');
                }
            }
        });
    }
}
