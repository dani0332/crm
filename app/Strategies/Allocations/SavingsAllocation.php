<?php

namespace App\Strategies\Allocations;

use App\Enums\RolesEnum;

class SavingsAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::SavingsAdvisor])->first();
    }
}
