<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Thrown when ApplicationStorage keys for EP Sage reversal failure emails are missing or inactive.
 */
class EpReversalFailureEmailConfigNotFoundException extends Exception
{
    /**
     * @param  array<int, string>  $missingKeys
     */
    public function __construct(
        public readonly array $missingKeys = [],
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        if ($message === '') {
            $message = 'EP failure email configuration not found for Sage reversal notification';
            if ($missingKeys !== []) {
                $message .= ': '.implode(', ', $missingKeys);
            }
        }

        parent::__construct($message, $code, $previous);
    }
}
