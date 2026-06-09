<?php

namespace App\Exceptions;

use Exception;

class BookingValidationException extends Exception
{
    public function __construct(public readonly array $errors, ?\Throwable $previous = null)
    {
        parent::__construct(implode(' ', $errors), 0, $previous);
    }
}
