<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;

class CycleAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {

        $emails = app(RuleService::class)->getEmailsByLeadSource($this->lead->source, $this->lead->quote_type_id);
        if (count($emails) > 0) {
            LoggerService::info(self::class.": Found advisor emails from rules | quote Ref-ID: {$this->lead->uuid} ", ['emails' => $emails]);

            return $this->getAdvisorByEmails($onlineStatus, $emails);
        }

        $this->skipRuleUsers = true;

        $emails = $this->getAdvisorEmails(ApplicationStorageEnums::CYCLE_ADVISORS);

        return $this->getAdvisorByEmails($onlineStatus, $emails);
    }
    public function getAdvisorByEmails($onlineStatus, $emails)
    {
        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CycleAdvisor])
            ->whereIn('users.email', $emails)
            ->logRawSql()
            ->first();
    }
}
