<?php

namespace App\Services\AML;

use App\Enums\AMLDecisionStatusEnum;
use App\Models\AML;
use Illuminate\Support\Collection;

class AMLResultsProcessor
{
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

    private function parseResults(?string $results): ?object
    {
        if (empty($results)) {
            return null;
        }

        $decoded = json_decode($results);

        return collect($decoded)->first();
    }

    private function extractManualStatusUpdates(object $amlResults): Collection
    {
        return collect($amlResults->ManualStatusUpdateIM ?? []);
    }

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

    private function determineDecision(
        object $match,
        Collection $manualStatusUpdates
    ): string {
        // If not false positive and not true match, check manual updates
        if (! $match->FalsePositive && ! $match->TrueMatch) {
            // Check for manual status update
            if ($manualStatusUpdates->has($match->ID)) {
                return $manualStatusUpdates->get($match->ID);
            }

            // Default to unknown
            return AMLDecisionStatusEnum::UNKNOWN;
        }

        // If marked as true match (or false positive - which shouldn't happen due to filter)
        return AMLDecisionStatusEnum::TRUE_MATCH;
    }
}
