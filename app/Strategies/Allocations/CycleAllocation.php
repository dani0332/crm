<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;

class CycleAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        $emails = $this->getAdvisorEmails(ApplicationStorageEnums::CYCLE_ADVISORS);

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CycleAdvisor])
            ->whereIn('users.email', $emails)
            ->first();
    }
}
