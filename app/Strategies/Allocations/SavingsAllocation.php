<?php

namespace App\Strategies\Allocations;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\AllocationConfigurer;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;

class SavingsAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        $roles = [RolesEnum::SavingsAdvisor, RolesEnum::SavingsManager];

        if ($this->lead->isFIC(QuoteTypes::SAVINGS)) {
            LoggerService::info(self::class.'::fetchAdvisor - Lead is FIC, fetching FIC rule users');
            $userIds = app(RuleService::class)->getFicRulesUsers(QuoteTypes::SAVINGS);
            LoggerService::info(self::class.'::fetchAdvisor - Lead is FIC, fetching FIC rule users', ['userIds' => $userIds]);

            return $this->getAdvisorsByEmailsOrIds($onlineStatus, $roles, advisorIds: $userIds);
        }

        $emails = app(RuleService::class)->getEmailsByLeadSource($this->lead->source, QuoteTypeId::Savings);

        if (count($emails) > 0) {
            LoggerService::info(self::class.": Found advisor emails from rules | quote Ref-ID: {$this->lead->uuid} ", ['emails' => $emails]);

            return $this->getAdvisorsByEmailsOrIds($onlineStatus, $roles, emails: $emails);
        }

        $this->skipRuleUsers = true;

        $advisorIds = AllocationConfigurer::getSavingsEligibleAdvisorIds($this->lead);

        return $this->getAdvisorsByEmailsOrIds($onlineStatus, $roles, advisorIds: $advisorIds);
    }
}
