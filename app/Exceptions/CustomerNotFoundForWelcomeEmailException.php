<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a welcome-email inbound payload references a customer email that does not exist.
 * SQS consumers can retry until the customer exists; the HTTP API maps this to 404.
 */
class CustomerNotFoundForWelcomeEmailException extends Exception
{
    public function __construct(
        public readonly string $customerEmail,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        if ($message === '') {
            $message = 'Customer not found for welcome email: '.$customerEmail;
        }

        parent::__construct($message, $code, $previous);
    }
}
