<?php

namespace App\Http\Requests;

use App\Models\HealthQuote;
use Illuminate\Foundation\Http\FormRequest;

class MemberDeleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $quote = HealthQuote::where('uuid', request()->quoteId)->first();
            if ($quote?->is_quote_locked) {
                $validator->errors()->add('error', 'Edits are not permitted once the lead has reached Transaction Approved status');
            }
        });
    }
}
