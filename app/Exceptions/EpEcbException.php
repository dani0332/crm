<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Custom exception for EpEcb service operations
 * Used for customer-facing error messages and API failures
 */
class EpEcbException extends Exception
{
    public function __construct($message = '', $code = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
