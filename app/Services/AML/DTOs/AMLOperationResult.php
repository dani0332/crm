<?php

namespace App\Services\AML\DTOs;

/**
 * Generic Data Transfer Object for AML operation results
 * Used for entity operations, insured searches, and other AML operations
 * that return a status, response object, and message
 */
class AMLOperationResult
{
    public function __construct(
        public readonly bool $status,
        public readonly mixed $response,
        public readonly string $message
    ) {}

    /**
     * Convert to JSON response array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'response' => $this->response,
            'message' => $this->message,
        ];
    }

    /**
     * Create a successful result
     *
     * @param mixed $response
     * @param string $message
     * @return self
     */
    public static function success(mixed $response, string $message): self
    {
        return new self(
            status: true,
            response: $response,
            message: $message
        );
    }

    /**
     * Create a failed result
     *
     * @param string $message
     * @return self
     */
    public static function failure(string $message): self
    {
        return new self(
            status: false,
            response: null,
            message: $message
        );
    }
}

