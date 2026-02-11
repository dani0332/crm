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
        if ($this->lead->isFIC(QuoteTypes::SAVINGS)) {
            LoggerService::info(self::class.'::fetchAdvisor - Lead is FIC, fetching FIC rule users');
            $userIds = app(RuleService::class)->getFicRulesUsers(QuoteTypes::SAVINGS);
            LoggerService::info(self::class.'::fetchAdvisor - Lead is FIC, fetching FIC rule users', ['userIds' => $userIds]);

            return $this->findAdvisorByEmails($onlineStatus, ids: $userIds);
        }

        $emails = app(RuleService::class)->getEmailsByLeadSource($this->lead->source, QuoteTypeId::Savings);

        if (count($emails) > 0) {
            LoggerService::info(self::class.": Found advisor emails from rules | quote Ref-ID: {$this->lead->uuid} ", ['emails' => $emails]);

            return $this->findAdvisorByEmails($onlineStatus, emails: $emails);
        }

        $this->skipRuleUsers = true;

        $advisorIds = AllocationConfigurer::getSavingsEligibleAdvisorIds($this->lead);

        return $this->findAdvisorByEmails($onlineStatus, ids: $advisorIds);
    }

    private function findAdvisorByEmails($onlineStatus, $emails = null, $ids = null)
    {
        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::SavingsAdvisor, RolesEnum::SavingsManager])
            ->when(! is_null($emails), fn ($q) => $q->whereIn('users.email', $emails))
            ->when(! is_null($ids), fn ($q) => $q->whereIn('users.id', $ids))
            ->logRawSql()
            ->first();
    }
}
