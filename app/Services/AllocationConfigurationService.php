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
        return QuoteType::withActive()->whereIn('short_code', [QuoteTypeShortCode::SAV])->get();
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
