<?php

namespace App\Services\AML\DTOs;

use App\Models\Entity;

/**
 * Data Transfer Object for Entity Link operation result
 * Encapsulates the result of linking an entity to a quote
 */
class EntityLinkResult
{
    public function __construct(
        public readonly bool $status,
        public readonly ?Entity $entity,
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
            'response' => $this->entity,
            'message' => $this->message,
        ];
    }
}

