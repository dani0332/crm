<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

class BirdWebhookException extends Exception
{
    public const WEBHOOK_FAILED = 1001;
    public const WEBHOOK_URL_NOT_FOUND = 1002;

    private ?int $httpStatusCode;

    /**
     * Create a new Bird webhook exception instance.
     *
     * @param string $message
     * @param int $code
     * @param int|null $httpStatusCode
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = 'Bird webhook request failed',
        int $code = self::WEBHOOK_FAILED,
        ?int $httpStatusCode = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->httpStatusCode = $httpStatusCode;
    }

    /**
     * Get the HTTP status code returned by Bird webhook
     *
     * @return int|null
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }
}
