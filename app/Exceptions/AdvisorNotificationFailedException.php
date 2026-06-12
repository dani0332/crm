<?php

namespace App\Exceptions;

use Exception;

class AdvisorNotificationFailedException extends Exception
{
    public function __construct(string $message = 'Failed to send advisor payment notification email', int $statusCode = 0, ?\Throwable $previous = null)
    {
        $fullMessage = $message.($statusCode ? ". Status: {$statusCode}" : '. Status: unknown');
        parent::__construct($fullMessage, $statusCode, $previous);
    }
}
