<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvestmentFrequencyEnum;
use App\Enums\QuoteTypes;
use App\Models\Allocation\AllocationConfiguration;
use App\Models\Nationality;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AllocationConfigurationService
{
    public function createConfiguration(array $data, int $userId): AllocationConfiguration
    {
        return DB::transaction(function () use ($data, $userId) {
            return AllocationConfiguration::create([
                'quote_type_id' => $data['quote_type_id'],
                'quote_type' => $data['quote_type'],
                'lumpsum_brackets' => $data['lumpsum_brackets'] ?? [],
                'regular_brackets' => $data['regular_brackets'] ?? [],
                'history' => [[
                    'ip' => request()->ip(),
                    'user_id' => $userId,
                    'action' => 'create',
                    'changes' => [
                        'old' => [],
                        'new' => $data,
                    ],
                ]],
                'created_by' => $userId,
            ]);
        });
    }

    public function updateConfiguration(AllocationConfiguration $configuration, array $data, int $userId): AllocationConfiguration
    {
        return DB::transaction(function () use ($configuration, $data, $userId) {
            $oldData = $configuration->toArray();

            // Update the configuration
            $configuration->update([
                'quote_type_id' => $data['quote_type_id'],
                'quote_type' => $data['quote_type'],
                'lumpsum_brackets' => $data['lumpsum_brackets'] ?? [],
                'regular_brackets' => $data['regular_brackets'] ?? [],
            ]);

            // Add to history
            $history = $configuration->history ?? [];
            $history[] = [
                'ip' => request()->ip(),
                'user_id' => $userId,
                'action' => 'update',
                'changes' => [
                    'old' => $oldData,
                    'new' => $data,
                ],
            ];

            $configuration->update(['history' => $history]);

            return $configuration->fresh();
        });
    }

    public function getNationalities(): Collection
    {
        return Nationality::where('is_active', 1)->get();
    }

    public function getEligibleAdvisorIds(QuoteTypes $quoteType, InvestmentFrequencyEnum $investmentFrequency, float $amount, int $nationalityId): array
    {
        $configuration = $this->getConfig($quoteType);

        if (! $configuration) {
            return [];
        }

        $brackets = $investmentFrequency === InvestmentFrequencyEnum::LUMPSUM
            ? $configuration->lumpsum_brackets
            : $configuration->regular_brackets;

        $matchingBracket = $brackets
            ->where('min', '<=', $amount)
            ->where('max', '>=', $amount)
            ->first();

        if (! $matchingBracket) {
            return [];
        }

        $profiles = collect($matchingBracket['profiles']);

        $matchingProfile = $profiles
            ->first(fn ($profile) => in_array($nationalityId, $profile['nationalityIds']));

        return $matchingProfile ? ($matchingProfile['advisorIds'] ?? []) : [];
    }

    public function getConfig(QuoteTypes $quoteType): ?AllocationConfiguration
    {
        return AllocationConfiguration::where('quote_type', $quoteType)->latest()->first();
    }
}
