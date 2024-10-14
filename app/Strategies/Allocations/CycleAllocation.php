<?php

namespace App\Strategies\Allocations;

use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;

class CycleAllocation extends BaseAllocation
{
    protected function resolveLead(): void
    {
        $this->lead = $this->quoteType->model()
            ->where('uuid', $this->uuid)
            ->where('quote_type_id', $this->quoteType->id())
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->when(! $this->overrideAdvisorId, fn ($q) => $q->whereNull('advisor_id'))
            ->first();
    }

    protected function fetchAdvisor(int $onlineStatus)
    {
        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CycleAdvisor])->first();
    }
}
