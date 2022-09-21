<?php

namespace App\Rules;

use App\Traits\GenericQueriesAllLobs;
use Illuminate\Contracts\Validation\Rule;

class ValidateQuoteObject implements Rule
{
    use GenericQueriesAllLobs;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return (  $this->getQuoteObject(request()->quoteType, request()->quote_uuid));
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Quote not found.';
    }
}
