<?php

namespace App\Http\Requests;

use App\Models\CustomerMembers;
use App\Models\TravelQuote;
use App\Services\TravelQuoteService;
use Illuminate\Foundation\Http\FormRequest;

class TravelMemberDeleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'travel_member_id' => ['required', 'integer'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'travel_member_id' => $this->route('traveler'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $member = CustomerMembers::find($this->input('travel_member_id'));

            if (! $member) {
                $validator->errors()->add('travel_member_delete', 'The selected member could not be found.');
            }

            $quote = TravelQuote::find($member->quote_id);
            if (! $quote) {
                $validator->errors()->add('travel_member_delete', 'Quote not found.');
            }

            $payments = $quote->payments()->get();
            $hasAuthorizedSplit = $this->hasAuthorizedSplit($member->quote_id);

            dd($payments->isEmpty(), $hasAuthorizedSplit);


            if (! $payments->isEmpty()) {
                $hasAuthorizedSplit = $this->hasAuthorizedSplit($member->quote_id);
                dd($hasAuthorizedSplit);




                if (! $hasAuthorizedSplit) {
                    $validator->errors()->add('travel_member_delete', 'The selected member has authorized payment.');
                }

                $travelQuoteService = app(TravelQuoteService::class);
                if (! $travelQuoteService->travelMemberMayBeDeletedWhenLinkedPaymentIsTerminal($member)) {
                    $validator->errors()->add(
                        'travel_member_delete',
                        $travelQuoteService->travelMemberDeleteBlockedMessage()
                    );
                }
            }
        });
    }
}
