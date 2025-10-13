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
}
