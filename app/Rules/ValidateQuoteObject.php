<?php

namespace App\Rules;

use App\Traits\GenericQueriesAllLobs;
use Illuminate\Contracts\Validation\Rule;

class ValidateQuoteObject implements Rule
{
    use GenericQueriesAllLobs;

    private $quoteType;

    public function __construct()
    {
        $this->quoteType = (request()->quoteType) ?? request()->quote_type;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return $this->getQuoteObjectBy($this->quoteType, request()->quote_uuid, 'uuid');
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
