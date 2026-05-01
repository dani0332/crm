<?php

namespace App\Http\Requests;

use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Repositories\PersonalQuoteRepository;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

class ChangePrimaryContactRequest extends FormRequest
{
    use GenericQueriesAllLobs;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'key' => 'required|in:'.GenericRequestEnum::EMAIL.','.GenericRequestEnum::MOBILE_NO,
            'value' => 'required',
            'quote_id' => 'required',
            'quote_customer_id' => 'nullable',
            'quote_primary_email_address' => 'nullable',
            'quote_primary_mobile_no' => 'nullable',
            'keep_existing_primary_email' => 'nullable|numeric|in:0,1',
        ];

        if (request()->segment(1) == 'customer-additional-contact') {
            $rules['quote_type'] = 'required';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Use route parameter 'quoteId' instead of body parameter 'quote_id' to prevent bypass
            $routeQuoteId = $this->route('quoteId');
            $bodyQuoteId = $this->quote_id;

            // Ensure body parameter matches route parameter if both exist
            if ($routeQuoteId && $bodyQuoteId && $routeQuoteId != $bodyQuoteId) {
                $validator->errors()->add('error', 'Quote ID in request body does not match the URL parameter.');
            }

            if ($this->key === GenericRequestEnum::EMAIL) {
                // Prioritize route parameter over body parameter
                $quoteId = $routeQuoteId ?? $bodyQuoteId;
                $quote = PersonalQuoteRepository::findOrFail($quoteId);
                if (
                    in_array($quote->quote_status_id, [
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
