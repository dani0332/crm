<?php

namespace App\Exceptions;

use Exception;

class PolicyIssuanceModelNotFoundException extends Exception
{
    public function __construct(int $processId, ?\Throwable $previous = null)
    {
        $message = "Quote model not found for policy issuance process {$processId}";
        parent::__construct($message, 0, $previous);
    }
}
