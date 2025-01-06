<?php

namespace App\Strategies\Allocations;

use App\Enums\RolesEnum;

class YachtAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus, ?string $uuid = null)
    {
        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::YachtAdvisor])->first();
    }
}
