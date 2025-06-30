<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;

class CycleAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        $emails = $this->getAdvisorEmails(ApplicationStorageEnums::CYCLE_ADVISORS);
        $rules = app(RuleService::class)->getUsersByLeadSourceRules($this->lead->source, $this->lead->quote_type_id);
        if (count($rules) > 0) {
            $userIds = app(RuleService::class)->getUserIdsFromRuleRecords($rules);
            $emails = User::whereIn('id', $userIds)->pluck('email')->toArray();
            LoggerService::info(self::class.' - Applied rules users for Cycle Advisors: '.implode(',', $userIds)." | quote Ref-ID: {$this->lead->uuid} ");

            return $emails;
        }

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CycleAdvisor])
            ->whereIn('users.email', $emails)
            ->first();
    }
}
