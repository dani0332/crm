<?php

namespace App\Strategies\Allocations;

use App\Enums\InvestmentFrequencyEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Services\AllocationConfigurationService;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;

class SavingsAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        $emails = app(RuleService::class)->getEmailsByLeadSource($this->lead->source, QuoteTypeId::Savings);

        if (count($emails) > 0) {
            LoggerService::info(self::class.": Found advisor emails from rules | quote Ref-ID: {$this->lead->uuid} ", ['emails' => $emails]);

            return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::SavingsAdvisor, RolesEnum::SavingsManager])
                ->whereIn('users.email', $emails)
                ->logRawSql()
                ->first();
        }

        $this->skipRuleUsers = true;

        $advisorIds = $this->getApplicableAdvisorIds();

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::SavingsAdvisor, RolesEnum::SavingsManager])
            ->whereIn('users.id', $advisorIds)
            ->logRawSql()
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
