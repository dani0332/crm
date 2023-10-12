<?php

namespace App\Rules;

use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidateBase64 implements ValidationRule
{
    private $documentType;
    private $extensionErrorMessage;
    private $fileSizeErrorMessage;

    public function __construct($documentType)
    {
        $this->documentType = $documentType;
        $this->extensionErrorMessage = 'The :attribute must be a file of type: ' . (str_replace('.', '', $documentType->accepted_files));
        $this->fileSizeErrorMessage= "The :attribute must not be greater than ". $this->documentType->max_size * 1024 ." kilobytes";
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            @list($extension,,,$image_size) = getBase64FileInfo($value);

            $accepted_files = $this->documentType->accepted_files;
            if (!str_contains($accepted_files, $extension)) {
                $fail($this->extensionErrorMessage);
            }
            else if (($image_size / 1024) > ($this->documentType->max_size * 1024)){
                $fail($this->fileSizeErrorMessage);
            }
        } catch (Exception $exception) {
            $fail($this->extensionErrorMessage);
        }
    }
}
