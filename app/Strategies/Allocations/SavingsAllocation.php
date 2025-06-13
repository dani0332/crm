<?php

namespace App\Strategies\Allocations;

use App\Enums\InvestmentFrequencyEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Services\AllocationConfigurationService;

class SavingsAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        $advisorIds = $this->getApplicableAdvisorIds();

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::SavingsAdvisor, RolesEnum::SavingsManager])
            ->whereIn('users.id', $advisorIds)
            ->first();
    }

    private function getApplicableAdvisorIds()
    {
        $frequency = $this->lead?->savingsQuote?->investmentFrequency?->code;
        $frequency = InvestmentFrequencyEnum::from($frequency);
        $amount = $this->lead?->savingsQuote?->currency?->convertToUSD((float) $this->lead?->savingsQuote?->investment_amount ?? 0);
        $nationalityId = $this->lead?->nationality?->id;

        if (! $nationalityId) {
            return [];
        }

        return app(AllocationConfigurationService::class)->getEligibleAdvisorIds(QuoteTypes::SAVINGS, $frequency, $amount, $nationalityId);
    }
}
