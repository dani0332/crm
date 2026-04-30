<?php

declare(strict_types=1);

namespace App\Services\AllocationConfiguration;

use App\Enums\GroupMedicalRegionEnum;
use App\Enums\QuoteTypes;
use App\Enums\QuoteTypeShortCode;
use App\Models\Allocation\AllocationConfiguration;
use App\Models\Nationality;
use App\Models\QuoteType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AllocationConfigurationService
{
    use AllocationConfigurationFindable;

    private function resolveConfig(QuoteTypes $quoteType, array $data, array $existingConfig = []): array
    {
        return match ($quoteType) {
            QuoteTypes::SAVINGS => [
                'lumpsum_brackets' => $data['lumpsum_brackets'] ?? [],
                'regular_brackets' => $data['regular_brackets'] ?? [],
            ],
            QuoteTypes::HOME => [
                'value_brackets' => $data['value_brackets'] ?? [],
                'volume_brackets' => $data['volume_brackets'] ?? [],
            ],
            QuoteTypes::LIFE => [
                'type1_brackets' => $data['type1_brackets'] ?? [],
                'type2_brackets' => $data['type2_brackets'] ?? [],
                'type3_brackets' => $data['type3_brackets'] ?? [],
                'type4_brackets' => $data['type4_brackets'] ?? [],
            ],
            QuoteTypes::PET, QuoteTypes::YACHT, QuoteTypes::CYCLE => [
                'advisor_ids' => $data['advisor_ids'] ?? [],
            ],
            QuoteTypes::CORPLINE => [
                'value_profiles' => $data['value_profiles'] ?? [],
                'volume_profiles' => $data['volume_profiles'] ?? [],
            ],
            QuoteTypes::GROUP_MEDICAL => $this->resolveGroupMedicalConfig($data, $existingConfig),
            default => [],
        };
    }

    public function createConfiguration(QuoteTypes $quoteType, array $data, int $userId): AllocationConfiguration
    {
        return DB::transaction(function () use ($quoteType, $data, $userId) {
            $config = $this->resolveConfig($quoteType, $data);

            return AllocationConfiguration::create([
                'quote_type_id' => $data['quote_type_id'],
                'quote_type' => $data['quote_type'],
                'config' => $config,
                'created_by' => $userId,
            ]);
        });
    }

    public function updateConfiguration(AllocationConfiguration $configuration, QuoteTypes $quoteType, array $data, int $userId): AllocationConfiguration
    {
        return DB::transaction(function () use ($configuration, $quoteType, $data, $userId) {
            $existingConfig = $configuration->config ?? [];
            $config = $this->resolveConfig($quoteType, $data, $existingConfig);

            $configuration->update([
                'quote_type_id' => $data['quote_type_id'],
                'quote_type' => $data['quote_type'],
                'config' => $config,
                'updated_by' => $userId,
            ]);

            return $configuration->fresh();
        });
    }

    public function getNationalities(): Collection
    {
        return Nationality::withActive()->get();
    }

    public function getQuoteTypes()
    {
        $quoteTypes = QuoteType::withActive()->whereIn('short_code', [
            QuoteTypeShortCode::SAV,
            QuoteTypeShortCode::HOM,
            QuoteTypeShortCode::LIF,
            QuoteTypeShortCode::PET,
            QuoteTypeShortCode::YAC,
            QuoteTypeShortCode::CYC,
            QuoteTypeShortCode::BUS,
        ])->get();

        $businessType = $quoteTypes->firstWhere('short_code', QuoteTypeShortCode::BUS);
        if ($businessType) {
            $quoteTypes = $quoteTypes->reject(function ($type) {
                return $type->short_code === QuoteTypeShortCode::BUS;
            });

            $corplineType = clone $businessType;
            $corplineType->text = 'CorpLine';
            $corplineType->code = QuoteTypes::CORPLINE->value;
            $quoteTypes->push($corplineType);

            $groupMedicalType = clone $businessType;
            $groupMedicalType->text = 'Group Medical';
            $groupMedicalType->code = QuoteTypes::GROUP_MEDICAL->value;
            $quoteTypes->push($groupMedicalType);
        }

        $quoteTypes = $quoteTypes->sortBy('text');

        return $quoteTypes->values();
    }

    /**
     * Group Medical clients always send both regions (emitData); the inactive region often arrives as
     * { micro_brackets: [], non_micro_brackets: [] } while the stored config has brackets. Treat that
     * shape as "no change for this region" and keep the existing region. If either key is omitted,
     * use per-field merge so [] can still clear a bracket list and omitted keys keep prior values.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $existingConfig
     * @return array<string, array{micro_brackets: array, non_micro_brackets: array}>
     */
    private function resolveGroupMedicalConfig(array $data, array $existingConfig): array
    {
        $out = [];
        foreach (GroupMedicalRegionEnum::cases() as $region) {
            $key = $region->value;
            $out[$key] = $this->mergeGroupMedicalRegion(
                $data[$key] ?? null,
                $existingConfig[$key] ?? null,
            );
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>|null  $incoming
     * @param  array<string, mixed>|null  $existing
     * @return array{micro_brackets: array, non_micro_brackets: array}
     */
    private function mergeGroupMedicalRegion(?array $incoming, ?array $existing): array
    {
        if (! is_array($incoming)) {
            return is_array($existing) ? $this->normalizeGroupMedicalRegion($existing) : $this->emptyGroupMedicalRegion();
        }

        $bothKeysPresent = array_key_exists('micro_brackets', $incoming)
            && array_key_exists('non_micro_brackets', $incoming);
        $bothEmptyArrays = $bothKeysPresent
            && $incoming['micro_brackets'] === []
            && $incoming['non_micro_brackets'] === [];

        if (
            $bothKeysPresent
            && $bothEmptyArrays
            && is_array($existing)
            && $this->groupMedicalRegionHasBrackets($existing)
        ) {
            return $this->normalizeGroupMedicalRegion($existing);
        }

        $base = $existing ?? [];

        return [
            'micro_brackets' => array_key_exists('micro_brackets', $incoming)
                ? $incoming['micro_brackets']
                : ($base['micro_brackets'] ?? []),
            'non_micro_brackets' => array_key_exists('non_micro_brackets', $incoming)
                ? $incoming['non_micro_brackets']
                : ($base['non_micro_brackets'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $region
     */
    private function groupMedicalRegionHasBrackets(array $region): bool
    {
        $micro = $region['micro_brackets'] ?? [];
        $non = $region['non_micro_brackets'] ?? [];

        return (is_countable($micro) && count($micro) > 0)
            || (is_countable($non) && count($non) > 0);
    }

    /**
     * @param  array<string, mixed>  $region
     * @return array{micro_brackets: array, non_micro_brackets: array}
     */
    private function normalizeGroupMedicalRegion(array $region): array
    {
        return [
            'micro_brackets' => is_array($region['micro_brackets'] ?? null) ? $region['micro_brackets'] : [],
            'non_micro_brackets' => is_array($region['non_micro_brackets'] ?? null) ? $region['non_micro_brackets'] : [],
        ];
    }

    /**
     * @return array{micro_brackets: array, non_micro_brackets: array}
     */
    private function emptyGroupMedicalRegion(): array
    {
        return [
            'micro_brackets' => [],
            'non_micro_brackets' => [],
        ];
    }
}
