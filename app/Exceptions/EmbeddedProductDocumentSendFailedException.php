<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Thrown when the queued auto-send step after an EP manual document override returns a non-success result.
 */
class EmbeddedProductDocumentSendFailedException extends Exception
{
    public function __construct(string $message = 'Embedded product document send failed', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
