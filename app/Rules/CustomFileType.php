<?php

namespace App\Rules;

use App\Traits\GenericQueriesAllLobs;
use Illuminate\Contracts\Validation\Rule;

class CustomFileType implements Rule
{
    use GenericQueriesAllLobs;

    private $acceptedFile;

    public function __construct($acceptedFile)
    {
        $this->acceptedFile = $acceptedFile;
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
        $acceptedFileTypes = explode(',', $this->acceptedFile);
        $extension = strtolower($value->getClientOriginalExtension());

        return in_array('.'.$extension, $acceptedFileTypes);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The file must be a file of type: '.$this->acceptedFile;
    }
}
