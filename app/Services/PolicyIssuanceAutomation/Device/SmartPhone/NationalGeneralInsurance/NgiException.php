<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use Exception;

class NgiException extends Exception
{
    public const PROCESS_NOT_FOUND = 'process_not_found';
    public const QUOTE_NOT_FOUND = 'quote_not_found';
    public const POLICY_NUMBER_VALIDATION_FAILED = 'policy_number_validation_failed';
    public const API_CALL_FAILED = 'api_call_failed';
    public const DOCUMENT_DOWNLOAD_FAILED = 'document_download_failed';

    protected string $errorType;
    protected array $context;

    public function __construct(
        string $message,
        string $errorType = self::API_CALL_FAILED,
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
