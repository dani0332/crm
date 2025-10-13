<?php

declare(strict_types=1);

namespace App\Services\AllocationConfiguration;

use App\Enums\InvestmentFrequencyEnum;
use App\Enums\QuoteTypes;
use App\Models\Allocation\AllocationConfiguration;
use App\Models\PersonalQuote;

trait AllocationConfigurationFindable
{
    public function findConfig(QuoteTypes $quoteType): ?AllocationConfiguration
    {
        return AllocationConfiguration::where('quote_type', $quoteType)->latest()->first();
    }

    public function getSavingsEligibleAdvisorIds(PersonalQuote $lead): array
    {
        $configuration = $this->findConfig(QuoteTypes::SAVINGS);
        $nationalityId = $lead?->nationality?->id;

        if (! $configuration || ! $nationalityId) {
            return [];
        }

        $frequency = $lead?->savingsQuote?->investmentFrequency?->code;
        $investmentFrequency = InvestmentFrequencyEnum::from($frequency);
        $amount = $lead?->savingsQuote?->currency?->convertToUSD((float) $lead?->savingsQuote?->investment_amount ?? 0);

        $brackets = $investmentFrequency === InvestmentFrequencyEnum::LUMPSUM
            ? $configuration->lumpsum_brackets
            : $configuration->regular_brackets;

        return $this->extractAdvisorIdsFromBrackets($brackets, $amount, $nationalityId);
    }

    public function getCommonEligibleAdvisorIds(QuoteTypes $quoteType): array
    {
        $configuration = $this->findConfig($quoteType);

        if (! $configuration) {
            return [];
        }

        return $configuration->advisor_ids;
    }

    public function getLifeEligibleAdvisorIds(PersonalQuote $lead): array
    {
        $configuration = $this->findConfig(QuoteTypes::LIFE);
        $nationalityId = $lead?->nationality?->id;
        $amount = $lead?->lifeQuote?->currency?->convertToAED((float) $lead?->lifeQuote?->sum_insured_value ?? 0);

        if (! $configuration || ! $nationalityId || empty($amount)) {
            return [];
        }

        foreach (range(1, 4) as $type) {
            $advisorIds = $this->extractAdvisorIdsFromBrackets($configuration->{"type{$type}_brackets"}, $amount, $nationalityId);
            if (! empty($advisorIds)) {
                return $advisorIds;
            }
        }

        return [];
    }

    private function extractAdvisorIdsFromBrackets(array $brackets, float $amount, int $nationalityId): array
    {
        $matchingBracket = $this->getMatchingBracket($brackets, $amount);

        $matchingProfile = $this->getMatchingProfile($matchingBracket, $nationalityId);

        return $this->getAdvisorIds($matchingProfile);
    }

    private function getMatchingBracket(array $brackets, float $amount): ?array
    {
        return collect($brackets)
            ->where('min', '<=', $amount)
            ->where('max', '>=', $amount)
            ->first();
    }

    private function getMatchingProfile(?array $bracket, int $nationalityId): ?array
    {
        if (empty($bracket) || ! isset($bracket['profiles'])) {
            return null;
        }

        $profiles = collect($bracket['profiles']);

        return collect($profiles)->first(fn ($profile) => in_array($nationalityId, $profile['nationalityIds']));
    }

    private function getAdvisorIds(?array $profile): array
    {
        if (empty($profile) || ! isset($profile['advisorIds'])) {
            return [];
        }

        return $profile['advisorIds'];
    }
}
