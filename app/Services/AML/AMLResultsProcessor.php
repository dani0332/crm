<?php

namespace App\Services\AML;

use App\Enums\AMLDecisionStatusEnum;
use App\Models\AML;
use Illuminate\Support\Collection;

/**
 * Service for processing and filtering AML screening results
 * Handles JSON parsing, filtering, and decision determination
 */
class AMLResultsProcessor
{
    /**
     * Process and filter AML results
     */
    public function process(AML $aml): array
    {
        $amlResults = $this->parseResults($aml->results);

        if (empty($amlResults)) {
            return [];
        }

        $manualStatusUpdates = $this->extractManualStatusUpdates($amlResults);

        if (! isset($amlResults->Watchlist)) {
            return [];
        }

        return $this->filterAndEnrichMatches(
            $amlResults->Watchlist->Matches ?? [],
            $manualStatusUpdates
        );
    }

    /**
     * Parse JSON results safely
     */
    private function parseResults(?string $results): ?object
    {
        if (empty($results)) {
            return null;
        }

        $decoded = json_decode($results);

        return collect($decoded)->first();
    }

    /**
     * Extract manual status updates from results
     */
    private function extractManualStatusUpdates(object $amlResults): Collection
    {
        return collect($amlResults->ManualStatusUpdateIM ?? []);
    }

    /**
     * Filter and enrich watchlist matches with decisions
     */
    private function filterAndEnrichMatches(
        array $matches,
        Collection $manualStatusUpdates
    ): array {
        return collect($matches)
            ->filter(fn ($match) => $match->FalsePositive === false)
            ->map(function ($match) use ($manualStatusUpdates) {
                $match->decision = $this->determineDecision(
                    $match,
                    $manualStatusUpdates
                );

                return $match;
            })
            ->values()
            ->toArray();
    }

    /**
     * Determine the decision status for a match
     */
    private function determineDecision(
        object $match,
        Collection $manualStatusUpdates
    ): string {
        // If already marked as true match or false positive
        if ($match->TrueMatch || $match->FalsePositive) {
            return AMLDecisionStatusEnum::TRUE_MATCH;
        }

        // Check for manual status update
        if ($manualStatusUpdates->has($match->ID)) {
            return $manualStatusUpdates->get($match->ID);
        }

        // Default to unknown
        return AMLDecisionStatusEnum::UNKNOWN;
    }
}
