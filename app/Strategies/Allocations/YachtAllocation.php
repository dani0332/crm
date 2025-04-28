<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;

class YachtAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        $emails = $this->getAdvisorEmails(ApplicationStorageEnums::YACHT_ADVISORS);

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::YachtAdvisor])
            ->whereIn('users.email', $emails)
            ->first();
    }
}
