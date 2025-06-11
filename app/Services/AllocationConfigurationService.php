<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Allocation\AllocationConfiguration;
use App\Models\Nationality;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AllocationConfigurationService
{
    /**
     * Create a new allocation configuration
     */
    public function createConfiguration(array $data, int $userId): AllocationConfiguration
    {
        return DB::transaction(function () use ($data, $userId) {
            $configuration = AllocationConfiguration::create([
                'quote_type_id' => $data['quote_type_id'],
                'quote_type' => $data['quote_type'],
                'savings_brackets' => $data['savings_brackets'] ?? [],
                'history' => [[
                    'ip' => request()->ip(),
                    'user_id' => $userId,
                    'action' => 'create',
                    'changes' => [
                        'old' => [],
                        'new' => $data,
                    ],
                ]],
            ]);

            return $configuration;
        });
    }

    /**
     * Update an existing allocation configuration
     */
    public function updateConfiguration(AllocationConfiguration $configuration, array $data, int $userId): AllocationConfiguration
    {
        return DB::transaction(function () use ($configuration, $data, $userId) {
            $oldData = $configuration->toArray();

            // Update the configuration
            $configuration->update([
                'quote_type_id' => $data['quote_type_id'],
                'quote_type' => $data['quote_type'],
                'savings_brackets' => $data['savings_brackets'] ?? [],
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

    /**
     * Get all nationalities for dropdown
     */
    public function getNationalities(): Collection
    {
        return Nationality::where('is_active', 1)->get();
    }

    /**
     * Find applicable allocation configuration for a quote
     */
    public function findApplicableConfiguration(string $quoteType, array $criteria = []): ?AllocationConfiguration
    {
        return AllocationConfiguration::where('quote_type', $quoteType)->first();
    }

    /**
     * Get allocation profile for specific criteria
     */
    public function getAllocationProfile(string $quoteType, float $amount, int $nationalityId, string $frequency = 'lumpsum'): ?array
    {
        $configuration = $this->findApplicableConfiguration($quoteType);

        if (! $configuration) {
            return null;
        }

        $brackets = $frequency === 'lumpsum'
            ? ($configuration->lumpsum_brackets ?? [])
            : ($configuration->regular_brackets ?? []);

        foreach ($brackets as $bracket) {
            if ($amount >= $bracket['min'] && $amount <= $bracket['max']) {
                // Find matching profile based on nationality
                foreach ($bracket['profiles'] ?? [] as $profile) {
                    if (in_array($nationalityId, $profile['nationalityIds'] ?? [])) {
                        return $profile;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Validate bracket structure
     */
    public function validateBracketStructure(array $brackets): bool
    {
        foreach ($brackets as $bracket) {
            if (! isset($bracket['min']) || ! isset($bracket['max']) || ! isset($bracket['profiles'])) {
                return false;
            }

            if (! is_numeric($bracket['min']) || ! is_numeric($bracket['max'])) {
                return false;
            }

            if ($bracket['min'] > $bracket['max']) {
                return false;
            }

            foreach ($bracket['profiles'] as $profile) {
                if (! isset($profile['advisorIds']) || ! isset($profile['nationalityIds'])) {
                    return false;
                }

                if (! is_array($profile['advisorIds']) || ! is_array($profile['nationalityIds'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check for overlapping brackets
     */
    public function hasOverlappingBrackets(array $brackets): bool
    {
        $count = count($brackets);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $bracket1 = $brackets[$i];
                $bracket2 = $brackets[$j];

                // Check if brackets overlap
                if (
                    ($bracket1['min'] <= $bracket2['max'] && $bracket1['max'] >= $bracket2['min']) ||
                    ($bracket2['min'] <= $bracket1['max'] && $bracket2['max'] >= $bracket1['min'])
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get configuration by quote type
     */
    public function getConfigurationByQuoteType(string $quoteType): ?AllocationConfiguration
    {
        return AllocationConfiguration::where('quote_type', $quoteType)
            ->latest()
            ->first();
    }

    /**
     * Get applicable profile for given parameters
     */
    public function getApplicableProfile(string $quoteType, string $investmentType, float $amount, int $advisorId, int $nationalityId): ?array
    {
        $configuration = $this->getConfigurationByQuoteType($quoteType);

        if (! $configuration) {
            return null;
        }

        $brackets = $investmentType === 'lumpsum'
            ? $configuration->lumpsum_brackets
            : $configuration->regular_brackets;

        foreach ($brackets as $bracket) {
            if ($amount >= $bracket['min'] && $amount <= $bracket['max']) {
                foreach ($bracket['profiles'] as $profile) {
                    if (
                        in_array($advisorId, $profile['advisorIds']) &&
                        in_array($nationalityId, $profile['nationalityIds'])
                    ) {
                        return $profile;
                    }
                }
            }
        }

        return null;
    }
}
