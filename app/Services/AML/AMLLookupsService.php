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
                LookupsEnum::RESIDENT_STATUS,
                LookupsEnum::DOCUMENT_ID_TYPE,
                LookupsEnum::ENTITY_DOCUMENT_TYPE,
                LookupsEnum::MODE_OF_CONTACT,
                LookupsEnum::MODE_OF_DELIVERY,
                LookupsEnum::EMPLOYMENT_SECTOR,
                LookupsEnum::LEGAL_STRUCTURE,
                LookupsEnum::ISSUANCE_PLACE,
                LookupsEnum::ISSUING_AUTHORITY,
                LookupsEnum::COMPANY_POSITION,
                LookupsEnum::PROFESSIONAL_TITLE,
                LookupsEnum::UBO_RELATION,
                LookupsEnum::COMPANY_TYPE,
                LookupsEnum::MEMBER_RELATION,
            ];

            return Lookup::whereIn('key', $lookupsForAML)
                ->get()
                ->groupBy('key')
                ->mapWithKeys(fn ($item, $key) => [str_replace('-', '_', $key) => $item]);
        });
    }
}
