<?php

namespace App\Exceptions\Allocation;

use Exception;
use Throwable;

class AllocationException extends Exception
{
    public function __construct($message = '', $code = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
