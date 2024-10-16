<?php

namespace App\Strategies\Allocations;

use App\Enums\RolesEnum;

class CycleAllocation extends BaseAllocation
{
    protected function resolveLead(): void
    {
        $this->lead = $this->getLeadBaseQuery()->first();
    }

    protected function fetchAdvisor(int $onlineStatus)
    {
        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CycleAdvisor])->first();
    }
}
