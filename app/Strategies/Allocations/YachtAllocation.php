<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;
use App\Models\User;
use App\Services\RuleService;

class YachtAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {

        $rules = app(RuleService::class)->getUsersByLeadSourceRules($this->lead->source, $this->lead->quote_type_id);
        if (count($rules) > 0) {
            $userIds = app(RuleService::class)->getUserIdsFromRuleRecords($rules);
            $emails = User::whereIn('id', $userIds)->pluck('email')->toArray();

            return $emails;
        } else {
            $emails = $this->getAdvisorEmails(ApplicationStorageEnums::YACHT_ADVISORS);
        }

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::YachtAdvisor])
            ->whereIn('users.email', $emails)
            ->first();
    }
}
