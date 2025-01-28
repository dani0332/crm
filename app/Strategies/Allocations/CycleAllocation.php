<?php

namespace App\Strategies\Allocations;

use App\Enums\RolesEnum;

class CycleAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CycleAdvisor])->first();
    }
}
