<?php

declare(strict_types=1);

namespace App\Exceptions\MetLife;

use Exception;

/**
 * Exception thrown when MetLife Health Questionnaire sync fails.
 */
class HealthQuestionnaireSyncException extends Exception
{
    protected ?string $quoteUuid;

    protected array $responseData;

    /**
     * Create a new HealthQuestionnaireSyncException instance.
     *
     * @param  string  $message  The exception message
     * @param  string|null  $quoteUuid  The quote UUID associated with the sync
     * @param  array  $responseData  The API response data
     * @param  int  $code  The exception code
     * @param  \Throwable|null  $previous  The previous throwable
     */
    public function __construct(
        string $message,
        ?string $quoteUuid = null,
        array $responseData = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->quoteUuid = $quoteUuid;
        $this->responseData = $responseData;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the quote UUID associated with the failed sync.
     */
    public function getQuoteUuid(): ?string
    {
        return $this->quoteUuid;
    }

    /**
     * Get the API response data.
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }
}

