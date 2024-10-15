<?php

namespace App\Strategies\Allocations;

use App\Enums\RolesEnum;

class CycleAllocation extends BaseAllocation
{
    protected function resolveLead(): void
    {
        $this->lead = $this->getLeadBaseQuery()
            ->where('quote_type_id', $this->quoteType->id())
            ->first();
    }

    protected function fetchAdvisor(int $onlineStatus)
    {
        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CycleAdvisor])->first();
    }
}
