<?php

declare(strict_types=1);

namespace App\Exceptions\MetLife;

use Exception;

/**
 * General exception for MetLife integration operations.
 * 
 * This exception covers all MetLife-related failures with specific error types
 * for better context and debugging without over-engineering multiple exception classes.
 */
class MetLifeException extends Exception
{
    // Error type constants for categorization
    public const QUOTE_NOT_FOUND = 'quote_not_found';
    public const DOCUMENT_TYPE_NOT_FOUND = 'document_type_not_found';
    public const DOCUMENT_UPLOAD_FAILED = 'document_upload_failed';
    public const FETCH_FAILED = 'fetch_failed';
    public const QUESTIONNAIRE_NOT_FOUND = 'questionnaire_not_found';
    public const SYNC_FAILED = 'sync_failed';
    public const AUTH_FAILED = 'auth_failed';
    public const API_REQUEST_FAILED = 'api_request_failed';

    protected string $errorType;

    protected array $context;

    /**
     * Create a new MetLifeException instance.
     *
     * @param  string  $message  The exception message
     * @param  string  $errorType  The error type constant for categorization
     * @param  array  $context  Additional context data (quote_uuid, policy_number, etc.)
     * @param  int  $code  The exception code
     * @param  \Throwable|null  $previous  The previous throwable
     */
    public function __construct(
        string $message,
        string $errorType = self::API_REQUEST_FAILED,
        array $context = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->errorType = $errorType;
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the error type.
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * Get the context data.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get a specific context value.
     */
    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }
}

