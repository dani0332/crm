<?php

namespace App\Strategies\Allocations;

use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Facades\AllocationConfigurer;
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

        $advisorIds = AllocationConfigurer::getSavingsEligibleAdvisorIds($this->lead);


        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::SavingsAdvisor, RolesEnum::SavingsManager])
            ->whereIn('users.id', $advisorIds)
            ->logRawSql()
            ->first();
    }
}
