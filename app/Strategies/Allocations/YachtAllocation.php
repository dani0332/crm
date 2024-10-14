<?php

namespace App\Strategies\Allocations;

use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Models\User;

class YachtAllocation extends BaseAllocation
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
        return User::select('users.id as user_id')
            ->join('lead_allocation as la', 'la.user_id', '=', 'users.id')
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('users.status', $onlineStatus)
            ->where(function ($query) {
                $query->whereRaw('la.allocation_count < la.max_capacity')->orWhere('la.max_capacity', -1);
            })
            ->whereIn('r.name', [RolesEnum::YachtAdvisor])
            ->where('la.quote_type_id', $this->quoteType->id())
            ->where('users.is_active', true)
            ->orderBy('la.last_allocated', 'asc')
            ->first();
    }
}
