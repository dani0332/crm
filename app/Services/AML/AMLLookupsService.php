<?php

namespace App\Services\AML;

use App\Models\QuoteType;
use App\Services\AMLService;

/**
 * Service for handling AML lookups data
 * Manages standard and additional lookups based on quote type and provider
 */
class AMLLookupsService
{
    public function __construct(
        private readonly AMLService $amlService
    ) {}

    /**
     * Get lookups for quote with additional fields if enabled
     */
    public function getLookupsForQuote(
        QuoteType $quoteType,
        ?object $insuranceProvider,
        object $quoteRequest
    ): array {
        // Get standard AML lookups
        $lookups = $this->amlService->getAMLLookups();

        // Check if additional fields are enabled
        $isAddionalFieldsEnabled = $this->amlService->isAdditionalVehicleAndDriverDetailsEnabled(
            $quoteType?->code,
            $insuranceProvider?->code,
            $quoteRequest?->registration_type
        );

        // Merge additional lookups if enabled
        if ($isAddionalFieldsEnabled) {
            $additionalLookups = $this->amlService->getAdditionaVehicleDriverLookups(
                $quoteType->code,
                $insuranceProvider?->id,
                $quoteRequest?->source
            );

            return array_merge($lookups->toArray(), $additionalLookups);
        }

        return $lookups->toArray();
    }
}
