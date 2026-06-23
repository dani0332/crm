<?php

namespace App\Strategies\Allocations;

use App\Enums\LeadSourceEnum;
use App\Facades\AllocationConfigurer;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;

class CycleAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        // EA_IMCRM: bypass email-based routing — permission gate in getAdvisorBaseQuery handles filtering
        if ($this->lead?->source === LeadSourceEnum::EA_IMCRM) {
            LoggerService::info(self::class.'::fetchAdvisor - EA_IMCRM lead detected, bypassing email-based routing and using permission gate');

            return $this->getAdvisorsByEmailsOrIds($onlineStatus, $this->quoteType->advisorRoles());
        }

        $emails = app(RuleService::class)->getEmailsByLeadSource($this->lead->source, $this->lead->quote_type_id);

        if (count($emails) > 0) {
            LoggerService::info(self::class.": Found advisor emails from rules | quote Ref-ID: {$this->lead->uuid} ", ['emails' => $emails]);

            return $this->getAdvisorsByEmailsOrIds($onlineStatus, $this->quoteType->advisorRoles(), $emails);
        }

        $this->skipRuleUsers = true;

        $advisorIds = AllocationConfigurer::getCommonEligibleAdvisorIds($this->quoteType);

        return $this->getAdvisorsByEmailsOrIds($onlineStatus, $this->quoteType->advisorRoles(), null, $advisorIds);
    }
}
