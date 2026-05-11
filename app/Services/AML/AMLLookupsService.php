<?php

namespace App\Services\AML;

use App\Enums\LookupsEnum;
use App\Models\Lookup;
use App\Models\QuoteType;
use App\Services\AMLService;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AMLLookupsService
{
    private const CACHE_KEY_STANDARD = 'aml_lookups_standard_v2';

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
            $cacheKey = 'aml_lookups_provider_'.$insuranceProviderId.'_'.md5(implode(',', $lookupsKeys)).'_v2';

            return $this->cacheGroupedLookups($cacheKey, function () use ($lookupsKeys, $insuranceProviderId): Collection {
                return Lookup::whereIn('key', $lookupsKeys)
                    ->where('insurance_provider_id', $insuranceProviderId)
                    ->get()
                    ->groupBy('key')
                    ->mapWithKeys(fn ($item, $key) => [str_replace('-', '_', $key) => $item]);
            });
        }

        // Get standard AML lookups with caching
        return $this->cacheGroupedLookups(self::CACHE_KEY_STANDARD, function (): Collection {
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

    /**
     * Cache grouped lookups as plain arrays so deserialization cannot yield __PHP_Incomplete_Class.
     *
     * @param  Closure(): Collection<string, EloquentCollection<int, Lookup>>  $buildGroupedCollection
     * @return Collection<string, EloquentCollection<int, Lookup>>
     */
    private function cacheGroupedLookups(string $cacheKey, Closure $buildGroupedCollection): Collection
    {
        $ttl = now()->addHour();
        $packed = Cache::get($cacheKey);

        if (! is_array($packed)) {
            if ($packed !== null) {
                Cache::forget($cacheKey);
            }

            $packed = $this->groupedLookupsToSerializableArray($buildGroupedCollection());
            Cache::put($cacheKey, $packed, $ttl);
        }

        return $this->serializableArrayToGroupedCollection($packed);
    }

    /**
     * @param  Collection<string, EloquentCollection<int, Lookup>>  $grouped
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupedLookupsToSerializableArray(Collection $grouped): array
    {
        return $grouped->map(function ($items): array {
            /** @var EloquentCollection<int, Lookup> $items */
            return $items->map(fn (Lookup $lookup): array => $lookup->toArray())->values()->all();
        })->all();
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $packed
     * @return Collection<string, EloquentCollection<int, Lookup>>
     */
    private function serializableArrayToGroupedCollection(array $packed): Collection
    {
        return collect($packed)->map(
            fn (array $rows): EloquentCollection => Lookup::hydrate($rows)
        );
    }
}
