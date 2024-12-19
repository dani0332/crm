<?php

namespace App\Strategies\Allocations;

use App\Enums\RolesEnum;

class YachtAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::YachtAdvisor])->first();
    }
}
