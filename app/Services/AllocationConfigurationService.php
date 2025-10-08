<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvestmentFrequencyEnum;
use App\Enums\QuoteTypes;
use App\Enums\QuoteTypeShortCode;
use App\Models\Allocation\AllocationConfiguration;
use App\Models\Nationality;
use App\Models\QuoteType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AllocationConfigurationService
{
    private function resolveConfig(QuoteTypes $quoteType, array $data): array
    {
        if ($quoteType === QuoteTypes::SAVINGS) {
            return [
                'lumpsum_brackets' => $data['lumpsum_brackets'] ?? [],
                'regular_brackets' => $data['regular_brackets'] ?? [],
            ];
        }

        if ($quoteType === QuoteTypes::HOME) {
            return [
                'value_brackets' => $data['value_brackets'] ?? [],
                'volume_brackets' => $data['volume_brackets'] ?? [],
            ];
        }

        if ($quoteType === QuoteTypes::LIFE) {
            return [
                'type1_brackets' => $data['type1_brackets'] ?? [],
                'type2_brackets' => $data['type2_brackets'] ?? [],
                'type3_brackets' => $data['type3_brackets'] ?? [],
                'type4_brackets' => $data['type4_brackets'] ?? [],
            ];
        }

        if ($quoteType === QuoteTypes::PET || $quoteType === QuoteTypes::YACHT || $quoteType === QuoteTypes::CYCLE) {
            return [
                'brackets' => $data['brackets'] ?? [],
            ];
        }

        if ($quoteType === QuoteTypes::CORPLINE) {
            return [
                'value_brackets' => $data['value_brackets'] ?? [],
                'volume_brackets' => $data['volume_brackets'] ?? [],
            ];
        }

        if ($quoteType === QuoteTypes::GROUP_MEDICAL) {
            return [
                'micro_brackets' => $data['micro_brackets'] ?? [],
                'non_micro_brackets' => $data['non_micro_brackets'] ?? [],
            ];
        }

        return [];
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
            QuoteTypeShortCode::BUS, // For Corpline & Group Medical subtypes
        ])->get();

        // Find and remove Business type from the collection
        $businessType = $quoteTypes->firstWhere('short_code', QuoteTypeShortCode::BUS);
        if ($businessType) {
            // Remove Business Insurance from the list
            $quoteTypes = $quoteTypes->reject(function ($type) {
                return $type->short_code === QuoteTypeShortCode::BUS;
            });

            // Add Corpline as a standalone entry (using Business ID underneath)
            $corplineType = clone $businessType;
            $corplineType->text = 'CorpLine';
            $corplineType->code = QuoteTypes::CORPLINE->value;
            $quoteTypes->push($corplineType);

            // Add Group Medical as another subtype of Business
            $groupMedicalType = clone $businessType;
            $groupMedicalType->text = 'Group Medical';
            $groupMedicalType->code = QuoteTypes::GROUP_MEDICAL->value;
            $quoteTypes->push($groupMedicalType);
        }

        // Reset collection keys and return as array-like collection
        return $quoteTypes->values();
    }

    public function getEligibleAdvisorIds(QuoteTypes $quoteType, InvestmentFrequencyEnum $investmentFrequency, float $amount, int $nationalityId): array
    {
        $configuration = $this->findConfig($quoteType);

        if (! $configuration) {
            return [];
        }

        $brackets = $investmentFrequency === InvestmentFrequencyEnum::LUMPSUM
            ? $configuration->lumpsum_brackets
            : $configuration->regular_brackets;

        $brackets = collect($brackets);

        $matchingBracket = $brackets
            ->where('min', '<=', $amount)
            ->where('max', '>=', $amount)
            ->first();

        if (! $matchingBracket) {
            return [];
        }

        $profiles = collect($matchingBracket['profiles']);

        $matchingProfile = $profiles->first(fn ($profile) => in_array($nationalityId, $profile['nationalityIds']));

        return $matchingProfile ? ($matchingProfile['advisorIds'] ?? []) : [];
    }

    public function findConfig(QuoteTypes $quoteType): ?AllocationConfiguration
    {
        return AllocationConfiguration::where('quote_type', $quoteType)->latest()->first();
    }
}
