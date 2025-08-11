<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;

class YachtAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        $emails = app(RuleService::class)->getEmailsByLeadSource($this->lead->source, $this->lead->quote_type_id);
        if (count($emails) > 0) {
            LoggerService::info(self::class.": Found advisor emails from rules | quote Ref-ID: {$this->lead->uuid} ", ['emails' => $emails]);

            return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::YachtAdvisor])
                ->whereIn('users.email', $emails)
                ->first();
        } else {
            $emails = $this->getAdvisorEmails(ApplicationStorageEnums::YACHT_ADVISORS);
        }

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::YachtAdvisor])
            ->whereIn('users.email', $emails)
            ->first();
    }
}
