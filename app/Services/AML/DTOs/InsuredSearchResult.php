<?php

namespace App\Services\AML\DTOs;

use App\Models\Insured;

/**
 * Data Transfer Object for Insured Search operation result
 * Encapsulates the result of searching for an insured person or entity
 */
class InsuredSearchResult
{
    public function __construct(
        public readonly bool $status,
        public readonly ?Insured $insured,
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
            'response' => $this->insured,
            'message' => $this->message,
        ];
    }
}

