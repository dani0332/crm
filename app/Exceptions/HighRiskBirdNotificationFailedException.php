<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class HighRiskBirdNotificationFailedException extends Exception
{
    public function __construct(string $message = 'Failed to trigger Bird high-risk AML workflow', int $statusCode = 0, ?\Throwable $previous = null)
    {
        $fullMessage = $message.($statusCode ? ". Status: {$statusCode}" : '. Status: unknown');
        parent::__construct($fullMessage, $statusCode, $previous);
    }
}
