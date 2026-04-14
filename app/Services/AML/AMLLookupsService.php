<?php

namespace App\Services\AML;

use App\Enums\LookupsEnum;
use App\Models\Lookup;
use App\Models\QuoteType;
use App\Services\AMLService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AMLLookupsService
{
    public function __construct(
        private readonly AMLService $amlService
    ) {}

    public function getLookupsForQuote(
        QuoteType $quoteType,
        ?object $insuranceProvider,
        object $quoteRequest
    ): array {
        // Get standard AML lookups
        $lookups = $this->getAMLLookups();

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

    public function getAMLLookups(?int $insuranceProviderId = null, array $lookupsKeys = []): Collection
    {
        // If insurance provider ID and lookup keys are provided, get provider-specific lookups
        if ($insuranceProviderId && ! empty($lookupsKeys)) {
            $lookupsKeys = array_map(fn (LookupsEnum $enum): string => $enum->value, $lookupsKeys);
            $cacheKey = 'aml_lookups_provider_'.$insuranceProviderId.'_'.md5(implode(',', $lookupsKeys));

            return Cache::remember($cacheKey, now()->addHour(), function () use ($lookupsKeys, $insuranceProviderId) {
                return Lookup::whereIn('key', $lookupsKeys)
                    ->where('insurance_provider_id', $insuranceProviderId)
                    ->get()
                    ->groupBy('key')
                    ->mapWithKeys(fn ($item, $key) => [str_replace('-', '_', $key) => $item]);
            });
        }

        // Get standard AML lookups with caching
        return Cache::remember('aml_lookups_standard', now()->addHour(), function () {
            $lookupsForAML = [
                LookupsEnum::RESIDENT_STATUS->value,
                LookupsEnum::DOCUMENT_ID_TYPE->value,
                LookupsEnum::ENTITY_DOCUMENT_TYPE->value,
                LookupsEnum::MODE_OF_CONTACT->value,
                LookupsEnum::MODE_OF_DELIVERY->value,
                LookupsEnum::EMPLOYMENT_SECTOR->value,
                LookupsEnum::LEGAL_STRUCTURE->value,
                LookupsEnum::ISSUANCE_PLACE->value,
                LookupsEnum::ISSUING_AUTHORITY->value,
                LookupsEnum::COMPANY_POSITION->value,
                LookupsEnum::PROFESSIONAL_TITLE->value,
                LookupsEnum::UBO_RELATION->value,
                LookupsEnum::COMPANY_TYPE->value,
                LookupsEnum::MEMBER_RELATION->value,
            ];

            return Lookup::whereIn('key', $lookupsForAML)
                ->get()
                ->groupBy('key')
                ->mapWithKeys(fn ($item, $key) => [str_replace('-', '_', $key) => $item]);
        });
    }
}
