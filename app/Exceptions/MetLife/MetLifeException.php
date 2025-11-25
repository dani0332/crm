<?php

declare(strict_types=1);

namespace App\Exceptions\MetLife;

use Exception;

class MetLifeException extends Exception
{
    public const METLIFE_INTEGRATION_DISABLED = 'metlife_integration_disabled';
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

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }
}
