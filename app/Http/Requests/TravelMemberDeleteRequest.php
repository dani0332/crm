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

                return;
            }

            $quote = TravelQuote::find($member->quote_id);
            if (! $quote) {
                $validator->errors()->add('travel_member_delete', 'Quote not found.');

                return;
            }

            $travelQuoteService = app(TravelQuoteService::class);
            $hasAuthorizedPayment = $travelQuoteService->memberHasAuthorizedPayment($member, $quote);

            if ($hasAuthorizedPayment) {
                $validator->errors()->add('travel_member_delete', 'Member cannot be deleted because payment has already been authorized.');
            }
        });
    }
}
