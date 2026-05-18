<?php

namespace App\Exceptions;

use Exception;

class PolicyIssuanceProcessNotFoundException extends Exception
{
    public function __construct(int $processId, ?\Throwable $previous = null)
    {
        $message = "Policy issuance process {$processId} not found";
        parent::__construct($message, 0, $previous);
    }
}
