<?php

namespace App\Exceptions;

use Exception;

class FetchPlansUpdateException extends Exception
{
    public function __construct(array $processIds, int $maxRetries, $code = 0, $previous = null)
    {
        $message = sprintf(
            'Failed to update fetch_plans_status to OUTDATED for process IDs: %s after %d deadlock retries.',
            implode(', ', $processIds),
            $maxRetries
        );
        parent::__construct($message, $code, $previous);
    }
}
