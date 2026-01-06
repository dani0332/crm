<?php

namespace App\Services\AML\DTOs;

use App\Models\QuoteType;
use Illuminate\Support\Collection;

/**
 * Data Transfer Object for AML Quote Details page data
 * Encapsulates all data needed for the AML detail/screening page
 */
class AMLQuoteDetailsData
{
    public function __construct(
        public readonly QuoteType $quoteType,
        public readonly object $quoteRequest,
        public readonly string $amlStatusName,
        public readonly Collection $kycLogs,
        public readonly array $lookups,
        public readonly Collection $nationalities,
        public readonly Collection $emirates,
        public readonly ?object $insuredDetails,
        public readonly array $entityDetails,
        public readonly Collection $membersDetails,
        public readonly Collection $uboDetails,
        public readonly string $cardHolderName,
        public readonly ?int $quoteAmlStatus,
        public readonly string $screeningType,
        public readonly string $gigInsurerDefaultEmail,
        public readonly int $isAnyEscalated,
        public readonly bool $isInsurerSyncEnabled,
        public readonly bool $isAddionalFieldsEnabled,
        public readonly bool $isPrivateCar,
        public readonly array $LIVAEnums,
        public readonly string $insurerName,
        public readonly array $enums,
        public readonly array $businessPayload,
        public readonly array $rtaConfigurationData
    ) {}

    /**
     * Convert to array for Inertia response
     */
    public function toArray(): array
    {
        return array_merge([
            'quoteType' => $this->quoteType,
            'quoteRequest' => $this->quoteRequest,
            'amlStatusName' => $this->amlStatusName,
            'kycLogs' => $this->kycLogs,
            'lookups' => $this->lookups,
            'nationalities' => $this->nationalities,
            'emirates' => $this->emirates,
            'insuredDetails' => $this->insuredDetails,
            'entityDetails' => $this->entityDetails,
            'membersDetails' => $this->membersDetails,
            'uboDetails' => $this->uboDetails,
            'cardHolderName' => $this->cardHolderName,
            'quoteAmlStatus' => $this->quoteAmlStatus,
            'screeningType' => $this->screeningType,
            'gigInsurerDefaultEmail' => $this->gigInsurerDefaultEmail,
            'isAnyEscalated' => $this->isAnyEscalated,
            'isInsurerSyncEnabled' => $this->isInsurerSyncEnabled,
            'isAddionalFieldsEnabled' => $this->isAddionalFieldsEnabled,
            'isPrivateCar' => $this->isPrivateCar,
            'LIVAEnums' => $this->LIVAEnums,
            'insurerName' => $this->insurerName,
            ...$this->enums,
        ], $this->businessPayload, $this->rtaConfigurationData);
    }
}
