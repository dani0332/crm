<?php

declare(strict_types=1);

namespace App\Services\AllocationConfiguration;

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

    private function resolveConfig(QuoteTypes $quoteType, array $data): array
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
            QuoteTypes::GROUP_MEDICAL => [
                'micro_brackets' => $data['micro_brackets'] ?? [],
                'non_micro_brackets' => $data['non_micro_brackets'] ?? [],
            ],
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
            $config = $this->resolveConfig($quoteType, $data);

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
}
