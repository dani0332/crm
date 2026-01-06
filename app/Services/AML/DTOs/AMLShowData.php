<?php

namespace App\Services\AML\DTOs;

use App\Models\AML;
use App\Models\Insured;

/**
 * Data Transfer Object for AML Show page data
 * Encapsulates all data needed for the AML detail view
 */
class AMLShowData
{
    public function __construct(
        public readonly AML $aml,
        public readonly array $amlResults,
        public readonly object $quoteObject,
        public readonly ?Insured $insured,
        public readonly ?int $customerId,
        public readonly array $enums
    ) {}

    /**
     * Convert to array for Inertia response
     */
    public function toArray(): array
    {
        return [
            'aml' => $this->aml,
            'amlResults' => $this->amlResults,
            'quoteObject' => $this->quoteObject,
            'insured' => $this->insured,
            'customerId' => $this->customerId,
            ...$this->enums,
        ];
    }
}
