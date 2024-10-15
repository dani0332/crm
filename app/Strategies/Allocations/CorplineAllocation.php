<?php

namespace App\Strategies\Allocations;

use App\Enums\RolesEnum;

class CorplineAllocation extends BaseAllocation
{
    protected function resolveLead(): void
    {
        $this->lead = $this->getLeadBaseQuery()->first();
    }

    protected function fetchAdvisor(int $onlineStatus)
    {
        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CorpLineAdvisor])->first();
    }
}
