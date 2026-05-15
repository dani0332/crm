<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Thrown when storing an embedded-product manual override document to blob storage fails.
 */
class EmbeddedProductDocumentUploadFailedException extends Exception
{
    public function __construct(
        string $message = 'Embedded product document upload failed',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
