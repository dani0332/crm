<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Custom exception for EpEcb service operations
 * Used for customer-facing error messages and API failures
 * 
 * This exception is not reported to logs by Laravel's default exception handler
 * because we handle logging manually in the service layer with custom formatting.
 */
class EpEcbException extends Exception
{
    public function __construct($message = '', $code = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Report the exception.
     * 
     * Returning false prevents Laravel from automatically logging this exception.
     * We handle logging manually in the service layer with custom formatting.
     *
     * @return bool
     */
    public function report(): bool
    {
        // Return false to prevent automatic logging by Laravel
        return false;
    }
}
