<?php

namespace App\Rules;

use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidateBase64 implements ValidationRule
{
    private $extensionErrorMessage;
    private $fileSizeErrorMessage;
    private $accepted_files;
    private $max_size;

    public function __construct($documentType)
    {
        $this->accepted_files = data_get($documentType, 'accepted_files', '.pdf');
        $this->extensionErrorMessage = 'The :attribute must be a file of type: '.(str_replace('.', '', $this->accepted_files));
        $this->max_size = data_get($documentType, 'max_size', 5);
        $this->fileSizeErrorMessage = 'The :attribute must not be greater than '.$this->max_size * 1024 .' kilobytes';
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            @[$extension, , , $image_size] = getBase64FileInfo($value);

            if (! str_contains($this->accepted_files, $extension)) {
                $fail($this->extensionErrorMessage);
            } elseif (($image_size / 1024) > ($this->max_size * 1024)) {
                $fail($this->fileSizeErrorMessage);
            }
        } catch (Exception $exception) {
            $fail($this->extensionErrorMessage);
        }
    }
}
