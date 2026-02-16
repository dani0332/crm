<?php

namespace App\Exceptions;

use Exception;

class BirdUrlNotFoundException extends Exception
{
    public function __construct(string $message = 'Bird URL not found for advisor payment notification', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
